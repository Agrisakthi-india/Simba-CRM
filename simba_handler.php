<?php
// simba_handler.php (Updated Version with Enhanced Business Search Detection)

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set content type early
header('Content-Type: application/json');

// Initialize output
$output = ["error" => "Unknown error", "debug" => []];
$debug = [];

// Define call_gemini_api function directly in this file
if (!function_exists('call_gemini_api')) {
    function call_gemini_api($prompt_text, $api_key) {
        if (empty($api_key)) {
            return ['error' => 'Your Gemini API key is not set in your Company Profile.'];
        }
        
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($api_key);
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt_text]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024
            ]
        ];
        
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: CRM-Assistant/1.0'
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("CURL Error: " . $curl_error);
            return ['error' => 'Network connection failed. Please try again later.'];
        }
        
        if ($http_code !== 200) {
            error_log("Gemini API HTTP Error: " . $http_code . " Response: " . $response);
            return ['error' => 'AI service temporarily unavailable. HTTP Code: ' . $http_code];
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg());
            return ['error' => 'Invalid response from AI service.'];
        }
        
        // Enhanced response parsing
        $generated_text = null;
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $generated_text = trim($result['candidates'][0]['content']['parts'][0]['text']);
        } elseif (isset($result['error'])) {
            return ['error' => 'AI Error: ' . ($result['error']['message'] ?? 'Unknown AI error')];
        }
        
        if (empty($generated_text)) {
            return ['error' => 'AI did not return a valid text response.'];
        }
        
        return ['success' => true, 'text' => $generated_text];
    }
}

try {
    $debug[] = "Starting handler";
    
    // Check if files exist before including
    $required_files = [
        'auth_check.php',
        __DIR__ . '/sme_config.php',
        __DIR__ . '/simba_actions.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file not found: " . $file);
        }
        $debug[] = "File exists: " . basename($file);
    }
    
    // Include required files
    require_once 'auth_check.php';
    $debug[] = "auth_check.php included";
    
    require_once __DIR__ . '/sme_config.php';
    $debug[] = "sme_config.php included";
    
    require_once __DIR__ . '/simba_actions.php';
    $debug[] = "simba_actions.php included";
    
    // Check session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('No user_id in session. Please login first.');
    }
    $debug[] = "Session validated";
    
    $user_id = $_SESSION['user_id'];
    $team_id = $_SESSION['team_id'] ?? 0;
    $account_role = $_SESSION['account_role'] ?? 'member';
    $is_superadmin = $_SESSION['is_superadmin'] ?? false;
    
    $debug[] = "Session data: user_id=$user_id, team_id=$team_id, role=$account_role";
    
    // Get and validate input
    $input_raw = file_get_contents('php://input');
    $debug[] = "Raw input received: " . substr($input_raw, 0, 100);
    
    $input = json_decode($input_raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    $debug[] = "JSON parsed successfully";
    
    $user_query = trim($input['query_text'] ?? '');
    if (empty($user_query)) {
        throw new Exception('Query cannot be empty');
    }
    $debug[] = "Query: " . $user_query;
    
    // Check database connection
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }
    $debug[] = "Database connection available";
    
    // Get user profile
    $stmt_profile = $pdo->prepare("SELECT * FROM company_profile WHERE user_id = ?");
    $stmt_profile->execute([$user_id]);
    $profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        throw new Exception('Company profile not found for user: ' . $user_id);
    }
    $debug[] = "Profile found";
    
    if (empty($profile['gemini_api_key'])) {
        throw new Exception('Gemini API key not configured in profile');
    }
    $debug[] = "API key available";
    
    // Get conversation history from input
    $history = $input['history'] ?? [];
    $debug[] = "History items: " . count($history);
    
    // Initialize Simba Actions
    $simba = new SimbaActions($pdo, $user_id, $team_id, $account_role, $is_superadmin);
    $debug[] = "SimbaActions initialized";
    
    // Enhanced intent detection for common commands
    $is_command = false;
    $command_response = '';
    
    // Task-related queries
    if (preg_match('/\b(task|tasks|todo|to-do|due|deadline)\b/i', $user_query)) {
        $is_command = true;
        // Determine time frame
        $time_frame = 'today';
        if (preg_match('/\b(tomorrow)\b/i', $user_query)) {
            $time_frame = 'tomorrow';
        } elseif (preg_match('/\b(this week|week)\b/i', $user_query)) {
            $time_frame = 'this_week';
        } elseif (preg_match('/\b(overdue|late)\b/i', $user_query)) {
            $time_frame = 'overdue';
        } elseif (preg_match('/\b(upcoming|future)\b/i', $user_query)) {
            $time_frame = 'upcoming';
        }
        
        $command_response = $simba->query_tasks(['time_frame' => $time_frame]);
        $debug[] = "Command detected: tasks ($time_frame)";
        
    // Lead counting queries
    } elseif (preg_match('/\bcount.*lead/i', $user_query) || preg_match('/how many.*lead/i', $user_query)) {
        $is_command = true;
        // Extract status if mentioned
        $status = 'Follow-up'; // default
        if (preg_match('/\b(follow-up|committed|not interested|pending)\b/i', $user_query, $matches)) {
            $status = $matches[1];
        }
        $command_response = $simba->count_leads(['status' => $status]);
        $debug[] = "Command detected: count leads";
        
    // Summary/overview queries
    } elseif (preg_match('/\b(summary|overview|dashboard|stats|statistics)\b/i', $user_query)) {
        $is_command = true;
        $command_response = $simba->get_lead_summary();
        $debug[] = "Command detected: summary";
        
    // Business search queries - Enhanced detection
    } elseif (preg_match('/\b(show|find|search|list|get).*\b(hospital|restaurant|school|shop|bank|pharmacy|hotel|gym|salon|garage|office|factory|market|temple|church|mosque|petrol|jewellery|textile|electronics|construction|transport)\b/i', $user_query) ||
              preg_match('/\b(hospital|restaurant|school|shop|bank|pharmacy|hotel|gym|salon|garage|office|factory|market|temple|church|mosque|petrol|jewellery|textile|electronics|construction|transport).*\bin\b.*\b(chennai|mumbai|delhi|bangalore|hyderabad|pune|kolkata|ahmedabad|kochi|[A-Z][a-zA-Z]+)\b/i', $user_query) ||
              preg_match('/\bfilter.*business/i', $user_query) ||
              preg_match('/\bsearch.*business/i', $user_query)) {
        
        $is_command = true;
        $params = [];
        
        // Extract category
        if (preg_match('/\b(hospital|restaurant|school|shop|bank|pharmacy|hotel|gym|salon|garage|office|factory|market|temple|church|mosque|petrol|jewellery|textile|electronics|construction|transport|hospitals|restaurants|schools|shops|banks|pharmacies|hotels|gyms|salons|garages|offices|factories|markets|temples|churches|mosques|jewellers|textiles)\b/i', $user_query, $matches)) {
            $params['category'] = $matches[1];
            $debug[] = "Extracted category: " . $matches[1];
        }
        
        // Extract city - Enhanced patterns for Indian cities
        if (preg_match('/\bin\s+([A-Z][a-zA-Z\s]+?)(?:\s|$|,|\?|!)/i', $user_query, $matches)) {
            $potential_city = trim($matches[1]);
            // Common Indian cities and their variations
            $indian_cities = [
                'Chennai', 'Mumbai', 'Delhi', 'Bangalore', 'Bengaluru', 'Hyderabad', 
                'Pune', 'Kolkata', 'Ahmedabad', 'Kochi', 'Cochin', 'Coimbatore',
                'Madurai', 'Salem', 'Trichy', 'Tiruchirappalli', 'Vellore', 'Tirunelveli',
                'Erode', 'Thoothukudi', 'Dindigul', 'Thanjavur', 'Cuddalore', 'Karur',
                'Tirupur', 'Nagercoil', 'Kumbakonam', 'Sivakasi', 'Virudhunagar'
            ];
            
            // Check if it's a known city or if it looks like a city name (capitalized, reasonable length)
            if (in_array($potential_city, $indian_cities) || 
                (strlen($potential_city) >= 3 && strlen($potential_city) <= 25 && 
                 !in_array(strtolower($potential_city), ['leads', 'business', 'businesses', 'filter', 'search', 'show', 'find', 'list']))) {
                $params['city'] = $potential_city;
                $debug[] = "Extracted city: " . $potential_city;
            }
        }
        
        // Extract district if mentioned
        if (preg_match('/\bdistrict\s+([A-Z][a-zA-Z\s]+?)(?:\s|$|,|\?|!)/i', $user_query, $matches)) {
            $params['district'] = trim($matches[1]);
            $debug[] = "Extracted district: " . $matches[1];
        }
        
        // Extract state if mentioned
        if (preg_match('/\bstate\s+([A-Z][a-zA-Z\s]+?)(?:\s|$|,|\?|!)/i', $user_query, $matches)) {
            $params['state'] = trim($matches[1]);
            $debug[] = "Extracted state: " . $matches[1];
        }
        
        // General search term if no specific filters found
        if (empty($params)) {
            // Try to extract a general search term
            if (preg_match('/\bfind\s+(.+?)(?:\s+in\s|$)/i', $user_query, $matches) ||
                preg_match('/\bsearch\s+(.+?)(?:\s+in\s|$)/i', $user_query, $matches) ||
                preg_match('/\bshow\s+(.+?)(?:\s+in\s|$)/i', $user_query, $matches)) {
                $search_term = trim($matches[1]);
                if (!empty($search_term) && strlen($search_term) < 50) {
                    $params['q'] = $search_term;
                    $debug[] = "Extracted search term: " . $search_term;
                }
            }
        }
        
        $command_response = $simba->filter_businesses($params);
        $debug[] = "Command detected: business search with params: " . json_encode($params);
        
    // Draft message queries
    } elseif (preg_match('/\b(draft|write|compose|create).*\b(message|text|whatsapp)/i', $user_query)) {
        $is_command = true;
        // Extract business/lead name
        $params = [];
        if (preg_match('/\bfor\s+(.+?)(?:\s|$)/i', $user_query, $matches)) {
            $params['lead_name'] = trim($matches[1]);
        }
        $command_response = $simba->generate_content($params, $profile);
        $debug[] = "Command detected: draft message";
        
    // Recent activity queries
    } elseif (preg_match('/\b(recent|latest).*\b(activity|activities|updates)/i', $user_query)) {
        $is_command = true;
        $params = [];
        if (preg_match('/\blast\s+(\d+)\s+days/i', $user_query, $matches)) {
            $params['days'] = intval($matches[1]);
        }
        $command_response = $simba->get_recent_activity($params);
        $debug[] = "Command detected: recent activity";
    }
    
    if ($is_command) {
        $debug[] = "Returning command response";
        
        // Check if response is JSON (special responses like business search)
        $json_response = json_decode($command_response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json_response['type'])) {
            $output = [
                'success' => true,
                'text' => $json_response['text'],
                'intent' => 'Command',
                'redirect_url' => $json_response['redirect_url'] ?? null,
                'response_type' => $json_response['type'] ?? null,
                'debug' => $debug
            ];
        } else {
            $output = [
                'success' => true,
                'text' => $command_response,
                'intent' => 'Command',
                'debug' => $debug
            ];
        }
    } else {
        // For general consultation, use conversational AI
        $debug[] = "Processing as consultation";
        
        $system_prompt = "You are Simba, a world-class business growth consultant and CRM expert, acting as a co-pilot for an SME owner.

**Your Persona:**
- You are professional, encouraging, and highly strategic.
- You are concise and provide actionable advice.
- All of your responses MUST be from the perspective of helping this specific user grow their business.

**This is the user's company profile (YOUR CONTEXT):**
- Company Name: " . ($profile['company_name'] ?? 'Not set') . "
- Services/Products: " . ($profile['services_description'] ?? 'Not set') . "
- Contact Person: " . ($profile['contact_person'] ?? 'Not set') . "

**Available Commands (mention these if relevant):**
- Check tasks: 'Show my tasks for today/tomorrow/this week'
- Count leads: 'How many follow-up leads do I have?'
- Search businesses: 'Show hospitals in Chennai' or 'Find restaurants in Mumbai'
- Get summary: 'Give me a business summary'
- Draft messages: 'Draft message for [Business Name]'

Based on this context, provide expert business growth consultation for the following query. Keep your response under 200 words and use markdown formatting for clarity.

USER QUERY: " . $user_query;
        
        $api_response = call_gemini_api($system_prompt, $profile['gemini_api_key']);
        $debug[] = "Gemini API called for consultation";
        
        if (isset($api_response['error'])) {
            throw new Exception('Gemini API Error: ' . $api_response['error']);
        }
        
        if (empty($api_response['text'])) {
            throw new Exception('Empty response from Gemini API');
        }
        
        $final_response = trim($api_response['text']);
        $debug[] = "Consultation response generated";
        
        $output = [
            'success' => true,
            'text' => $final_response,
            'intent' => 'Consultation',
            'debug' => $debug
        ];
    }
    
    http_response_code(200);
    
} catch (Exception $e) {
    $debug[] = "Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    
    $output = [
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug,
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ];
    
    http_response_code(500);
    
} catch (Error $e) {
    $debug[] = "Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    
    $output = [
        'success' => false,
        'error' => 'Fatal Error: ' . $e->getMessage(),
        'debug' => $debug,
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ];
    
    http_response_code(500);
}

// Ensure we always output something
echo json_encode($output, JSON_PRETTY_PRINT);
?>