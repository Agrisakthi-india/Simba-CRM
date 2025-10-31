<?php
// simba_actions.php (Updated Version with Enhanced Business Search)

class SimbaActions {
    private $pdo;
    private $user_id;
    private $team_id;
    private $account_role;
    private $is_superadmin;

    public function __construct($pdo, $user_id, $team_id, $account_role, $is_superadmin) {
        if (!$pdo || !($pdo instanceof PDO)) {
            throw new InvalidArgumentException('Valid PDO instance required');
        }
        
        $this->pdo = $pdo;
        $this->user_id = intval($user_id);
        $this->team_id = intval($team_id);
        $this->account_role = $account_role ?: 'member';
        $this->is_superadmin = (bool)$is_superadmin;
        
        if ($this->user_id <= 0) {
            throw new InvalidArgumentException('Valid user ID required');
        }
    }

    // --- PHASE 1: DATA RETRIEVAL ---

    public function query_tasks($params = []) {
        try {
            $time_frame = $params['time_frame'] ?? 'today';
            $sql_where = "t.status = 'Pending'";
            $sql_params = [];
            
            // Enhanced time frame handling
            switch (strtolower($time_frame)) {
                case 'today':
                    $sql_where .= " AND DATE(t.due_date) = CURDATE()";
                    break;
                case 'tomorrow':
                    $sql_where .= " AND DATE(t.due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
                    break;
                case 'this_week':
                    $sql_where .= " AND YEARWEEK(t.due_date) = YEARWEEK(CURDATE())";
                    break;
                case 'overdue':
                    $sql_where .= " AND t.due_date < CURDATE()";
                    break;
                case 'upcoming':
                    $sql_where .= " AND t.due_date >= CURDATE()";
                    break;
                default:
                    $sql_where .= " AND DATE(t.due_date) = CURDATE()";
            }

            // Permission-based filtering
            if (!$this->is_superadmin) {
                if ($this->account_role === 'admin') {
                    // Admins see all team tasks
                    $sql_where .= " AND l.team_id = ?";
                    $sql_params[] = $this->team_id;
                } else {
                    // Members see only their assigned tasks
                    $sql_where .= " AND t.user_id = ?";
                    $sql_params[] = $this->user_id;
                }
            }
            
            $sql = "SELECT t.id as task_id, t.title, t.due_date,
                           l.id as lead_id, b.name as company_name
                    FROM lead_tasks t 
                    JOIN leads l ON t.lead_id = l.id 
                    JOIN businesses b ON l.company_id = b.id 
                    WHERE " . $sql_where . " 
                    ORDER BY t.due_date ASC 
                    LIMIT 20";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($sql_params);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($tasks)) { 
                return "You have no " . str_replace('_', ' ', $time_frame) . " tasks. Great job! 🎉"; 
            }

            $response = "Here are your **" . str_replace('_', ' ', $time_frame) . " tasks** (" . count($tasks) . " total):\n\n";
            foreach ($tasks as $index => $task) {
                $due_date = date('M j, Y', strtotime($task['due_date']));
                $company_name = htmlspecialchars($task['company_name']);
                $task_title = htmlspecialchars($task['title']);
                $response .= ($index + 1) . ". **{$task_title}** for [{$company_name}](lead_details.php?id={$task['lead_id']}) due on {$due_date}\n";
            }
            return $response;
            
        } catch (PDOException $e) {
            error_log("Database error in query_tasks: " . $e->getMessage());
            return "Sorry, I couldn't retrieve your tasks right now. Please try again later.";
        } catch (Exception $e) {
            error_log("Error in query_tasks: " . $e->getMessage());
            return "An error occurred while fetching your tasks.";
        }
    }
    
    // Updated filter_businesses method (renamed from filter_leads for clarity)
    public function filter_businesses($params = []) {
        try {
            // Validate and sanitize parameters
            $allowed_params = ['category', 'city', 'district', 'state', 'q'];
            $clean_params = [];
            
            foreach ($params as $key => $value) {
                if (in_array($key, $allowed_params) && !empty($value)) {
                    $clean_params[$key] = htmlspecialchars(trim($value));
                }
            }
            
            // Normalize category if provided
            if (isset($clean_params['category'])) {
                $clean_params['category'] = $this->normalizeCategoryForSearch($clean_params['category']);
            }
            
            // Normalize city if provided
            if (isset($clean_params['city'])) {
                $clean_params['city'] = $this->normalizeCityForSearch($clean_params['city']);
            }
            
            if (empty($clean_params)) {
                return "Please specify search criteria like category, city, district, or business name.";
            }
            
            // Get count of matching businesses before showing results
            $count = $this->countBusinessesForFilter($clean_params);
            
            // Build user-friendly description
            $criteria = [];
            foreach ($clean_params as $key => $value) {
                switch ($key) {
                    case 'category':
                        $criteria[] = "Category: {$value}";
                        break;
                    case 'city':
                        $criteria[] = "City: {$value}";
                        break;
                    case 'district':
                        $criteria[] = "District: {$value}";
                        break;
                    case 'state':
                        $criteria[] = "State: {$value}";
                        break;
                    case 'q':
                        $criteria[] = "Search: {$value}";
                        break;
                }
            }
            
            // Create URL for businesses page (assuming index.php handles business filtering)
            $url = "index.php?" . http_build_query($clean_params);
            $criteria_text = implode(", ", $criteria);
            
            if ($count === 0) {
                return "❌ **No businesses found** matching: {$criteria_text}\n\n💡 Try searching with different criteria or check if the businesses are in your database.";
            }
            
            $response = "🏢 **Found {$count} businesses** matching: **{$criteria_text}**\n\n";
            $response .= "[🔍 **View All Results**]({$url})\n\n";
            $response .= "Click the link above to see all matching businesses with full details, contact information, and lead conversion options.";
            
            // Return response with redirect URL for JavaScript handling
            return json_encode([
                'type' => 'business_search',
                'text' => $response,
                'redirect_url' => $url,
                'count' => $count,
                'criteria' => $criteria_text
            ]);
            
        } catch (Exception $e) {
            error_log("Error in filter_businesses: " . $e->getMessage());
            return "Sorry, I couldn't process your search request. Please try again.";
        }
    }

    // Helper method to count businesses for filter
    private function countBusinessesForFilter($params) {
        try {
            $sql_where = "WHERE 1=1";
            $sql_params = [];
            
            // Permission-based filtering
            if (!$this->is_superadmin) {
                $sql_where .= " AND b.team_id = ?";
                $sql_params[] = $this->team_id;
            }
            
            // Apply filters
            if (!empty($params['category'])) {
                $sql_where .= " AND LOWER(b.category) = LOWER(?)";
                $sql_params[] = $params['category'];
            }
            
            if (!empty($params['city'])) {
                $sql_where .= " AND LOWER(b.city) = LOWER(?)";
                $sql_params[] = $params['city'];
            }
            
            if (!empty($params['district'])) {
                $sql_where .= " AND LOWER(b.district) = LOWER(?)";
                $sql_params[] = $params['district'];
            }
            
            if (!empty($params['state'])) {
                $sql_where .= " AND LOWER(b.state) = LOWER(?)";
                $sql_params[] = $params['state'];
            }
            
            if (!empty($params['q'])) {
                $sql_where .= " AND (LOWER(b.name) LIKE LOWER(?) OR LOWER(b.description) LIKE LOWER(?) OR LOWER(b.contact_person) LIKE LOWER(?))";
                $search_term = "%{$params['q']}%";
                $sql_params[] = $search_term;
                $sql_params[] = $search_term;
                $sql_params[] = $search_term;
            }
            
            $sql = "SELECT COUNT(*) FROM businesses b {$sql_where}";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($sql_params);
            
            return intval($stmt->fetchColumn());
            
        } catch (Exception $e) {
            error_log("Error in countBusinessesForFilter: " . $e->getMessage());
            return 0;
        }
    }

    // Updated filter_leads method for backward compatibility
    public function filter_leads($params = []) {
        // For lead filtering, we assume leads table exists and has company_id referencing businesses
        return $this->filter_businesses($params);
    }

    public function count_leads($params = []) {
        try {
            $status = trim($params['status'] ?? '');
            if (empty($status)) {
                return "Please specify a lead status (e.g., 'Follow-up', 'Committed', 'Not Interested').";
            }

            $sql_where = "WHERE l.status = ?";
            $sql_params = [$status];

            // Permission-based filtering
            if (!$this->is_superadmin) {
                if ($this->account_role === 'admin') {
                    // Admins see team-wide stats
                    $sql_where .= " AND l.team_id = ?";
                    $sql_params[] = $this->team_id;
                } else {
                    // Members see only their assigned leads
                    $sql_where .= " AND l.assigned_user_id = ?";
                    $sql_params[] = $this->user_id;
                }
            }

            // Get count and additional stats
            $sql = "SELECT COUNT(*) as total_count,
                           COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_count,
                           AVG(l.ai_score) as avg_score
                    FROM leads l 
                    JOIN businesses b ON l.company_id = b.id 
                    " . $sql_where;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($sql_params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            $total_count = intval($stats['total_count']);
            $recent_count = intval($stats['recent_count']);
            $avg_score = $stats['avg_score'] ? round(floatval($stats['avg_score']), 1) : null;

            $response = "📊 **Lead Statistics for '{$status}' status:**\n\n";
            $response .= "• Total leads: **{$total_count}**\n";
            
            if ($recent_count > 0) {
                $response .= "• Added this week: **{$recent_count}**\n";
            }
            
            if ($avg_score !== null) {
                $response .= "• Average AI Score: **{$avg_score}/100**\n";
            }
            
            if ($total_count === 0) {
                $response .= "\n💡 *No leads found with this status. Try searching for 'Follow-up', 'Committed', or 'Not Interested'.*";
            } else {
                $response .= "\n[📋 View All {$status} Leads](index.php?status=" . urlencode($status) . ")";
            }

            return $response;
            
        } catch (PDOException $e) {
            error_log("Database error in count_leads: " . $e->getMessage());
            return "Sorry, I couldn't retrieve lead statistics right now. Please try again later.";
        } catch (Exception $e) {
            error_log("Error in count_leads: " . $e->getMessage());
            return "An error occurred while counting leads.";
        }
    }

    // --- PHASE 2: CONTENT GENERATION ---

    public function generate_content($params = [], $profile = []) {
        try {
            $lead_name = trim($params['lead_name'] ?? '');
            if (empty($lead_name)) {
                return "Please specify which lead you'd like to draft a message for. You can say something like 'Draft message for ABC Restaurant'.";
            }

            // Enhanced lead search with fuzzy matching
            $sql = "SELECT l.id, l.status, b.name, b.category, b.city 
                    FROM leads l 
                    JOIN businesses b ON l.company_id = b.id 
                    WHERE (b.name LIKE ? OR b.name LIKE ?)";
            
            $search_exact = "%{$lead_name}%";
            $search_fuzzy = "%" . str_replace(' ', '%', $lead_name) . "%";
            $sql_params = [$search_exact, $search_fuzzy];

            // Permission filtering
            if (!$this->is_superadmin) {
                $sql .= " AND l.team_id = ?";
                $sql_params[] = $this->team_id;
                
                // Members can only draft for their assigned leads
                if ($this->account_role === 'member') {
                    $sql .= " AND l.assigned_user_id = ?";
                    $sql_params[] = $this->user_id;
                }
            }
            
            $sql .= " ORDER BY 
                        CASE WHEN b.name = ? THEN 1 
                             WHEN b.name LIKE ? THEN 2 
                             ELSE 3 END,
                        l.updated_at DESC 
                      LIMIT 5";
            
            $sql_params[] = $lead_name;
            $sql_params[] = "{$lead_name}%";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($sql_params);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($leads)) {
                return "Sorry, I couldn't find any lead named '{$lead_name}' that you have access to. Please check the spelling or try a different search term.";
            }

            // If multiple matches, ask for clarification
            if (count($leads) > 1) {
                $response = "I found multiple leads matching '{$lead_name}'. Which one would you like to draft a message for?\n\n";
                foreach ($leads as $index => $lead) {
                    $response .= ($index + 1) . ". **{$lead['name']}** ({$lead['category']}) - {$lead['city']}\n";
                }
                $response .= "\nPlease be more specific with the business name.";
                return $response;
            }

            // Single match found - proceed with drafting
            $lead = $leads[0];
            
            // Return special identifier for AI handler to process
            return "draft_message:" . $lead['id'];
            
        } catch (PDOException $e) {
            error_log("Database error in generate_content: " . $e->getMessage());
            return "Sorry, I couldn't access the lead information right now. Please try again later.";
        } catch (Exception $e) {
            error_log("Error in generate_content: " . $e->getMessage());
            return "An error occurred while searching for the lead.";
        }
    }

    // --- PHASE 3: ADDITIONAL HELPER FUNCTIONS ---

    public function get_recent_activity($params = []) {
        try {
            $limit = min(10, max(1, intval($params['limit'] ?? 5)));
            $days = min(30, max(1, intval($params['days'] ?? 7)));

            $sql_where = "WHERE l.updated_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $sql_params = [$days];

            if (!$this->is_superadmin) {
                if ($this->account_role === 'admin') {
                    $sql_where .= " AND l.team_id = ?";
                    $sql_params[] = $this->team_id;
                } else {
                    $sql_where .= " AND l.assigned_user_id = ?";
                    $sql_params[] = $this->user_id;
                }
            }

            $sql = "SELECT la.activity_type, la.activity_date, b.name as company_name, u.username as logged_by
                    FROM lead_activities la
                    JOIN leads l ON la.lead_id = l.id
                    JOIN businesses b ON l.company_id = b.id 
                    JOIN users u ON la.user_id = u.id
                    {$sql_where}
                    ORDER BY la.activity_date DESC 
                    LIMIT ?";
            
            $sql_params[] = $limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($sql_params);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($activities)) { 
                return "No recent activity in the last {$days} days."; 
            }

            $response = "📈 **Recent Activity** (last {$days} days):\n\n";
            foreach ($activities as $activity) {
                $time_ago = $this->timeAgo($activity['activity_date']);
                $company = htmlspecialchars($activity['company_name']);
                $type = htmlspecialchars($activity['activity_type']);
                $logged_by = htmlspecialchars($activity['logged_by']);
                $response .= "• **{$type}** for **{$company}**\n";
                $response .= "  *{$time_ago} by {$logged_by}*\n\n";
            }

            return $response;

        } catch (Exception $e) {
            error_log("Error in get_recent_activity: " . $e->getMessage());
            return "Sorry, I couldn't retrieve recent activity.";
        }
    }

    public function get_lead_summary($params = []) {
        try {
            $sql_where = "";
            $sql_params = [];

            if (!$this->is_superadmin) {
                if ($this->account_role === 'admin') {
                    $sql_where = "WHERE l.team_id = ?";
                    $sql_params[] = $this->team_id;
                } else {
                    $sql_where = "WHERE l.assigned_user_id = ?";
                    $sql_params[] = $this->user_id;
                }
            }

            $sql = "SELECT 
                        COUNT(*) as total_leads,
                        COUNT(CASE WHEN l.status = 'Follow-up' THEN 1 END) as followup_count,
                        COUNT(CASE WHEN l.status = 'Committed' THEN 1 END) as committed_count,
                        COUNT(CASE WHEN l.status = 'Not Interested' THEN 1 END) as not_interested_count,
                        COUNT(CASE WHEN l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_this_week,
                        AVG(l.ai_score) as avg_score
                    FROM leads l 
                    {$sql_where}";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($sql_params);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            $response = "📊 **Your Lead Summary:**\n\n";
            $response .= "• Total Leads: **{$summary['total_leads']}**\n";
            $response .= "• Follow-up: **{$summary['followup_count']}**\n";
            $response .= "• Committed: **{$summary['committed_count']}**\n";
            $response .= "• Not Interested: **{$summary['not_interested_count']}**\n";
            $response .= "• New This Week: **{$summary['new_this_week']}**\n";
            
            if ($summary['avg_score']) {
                $avg_score = round(floatval($summary['avg_score']), 1);
                $response .= "• Average Score: **{$avg_score}/100**\n";
            }

            return $response;

        } catch (Exception $e) {
            error_log("Error in get_lead_summary: " . $e->getMessage());
            return "Sorry, I couldn't generate the lead summary.";
        }
    }

    // --- UTILITY FUNCTIONS ---

    private function getPriorityIcon($priority) {
        switch (strtolower($priority)) {
            case 'high':
                return '🔴';
            case 'medium':
                return '🟡';
            case 'low':
                return '🟢';
            default:
                return '⚪';
        }
    }

    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        
        return date('M j, Y', strtotime($datetime));
    }

    // Method to handle unknown actions gracefully
    public function __call($method, $args) {
        error_log("Unknown method called in SimbaActions: {$method}");
        return "I'm sorry, I don't know how to handle that request yet. I can help you with:\n\n• Checking tasks\n• Counting leads\n• Searching businesses\n• Drafting messages\n• Getting activity summaries";
    }

    // --- CATEGORY NORMALIZATION HELPER ---
    private function normalizeCategoryForSearch($category) {
        try {
            $category = trim(strtolower($category));
            if (empty($category)) return $category;
            
            // Get all unique categories from the database for this user's scope
            $sql_where = "";
            $sql_params = [];
            
            if (!$this->is_superadmin) {
                $sql_where = "WHERE team_id = ?";
                $sql_params[] = $this->team_id;
            }
            
            $stmt = $this->pdo->prepare("SELECT DISTINCT LOWER(category) as category FROM businesses {$sql_where}");
            $stmt->execute($sql_params);
            $db_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Direct match first
            if (in_array($category, $db_categories)) {
                return $category;
            }
            
            // Enhanced category mappings with Indian context
            $category_mappings = [
                'hospital' => ['hospitals', 'medical center', 'medical centre', 'clinic', 'clinics', 'healthcare', 'nursing home', 'medical'],
                'restaurant' => ['restaurants', 'food', 'dining', 'eatery', 'eateries', 'cafe', 'cafes', 'hotel', 'hotels', 'food court'],
                'school' => ['schools', 'education', 'educational', 'academy', 'academies', 'institute', 'institutes', 'college', 'colleges'],
                'shop' => ['shops', 'store', 'stores', 'retail', 'shopping', 'mart', 'marts', 'showroom', 'showrooms'],
                'bank' => ['banks', 'banking', 'financial', 'finance', 'atm', 'cooperative bank'],
                'pharmacy' => ['pharmacies', 'medical store', 'medical stores', 'chemist', 'chemists', 'drug store'],
                'hotel' => ['hotels', 'accommodation', 'lodging', 'resort', 'resorts', 'guest house', 'lodge'],
                'gym' => ['gyms', 'fitness', 'fitness center', 'fitness centre', 'health club', 'yoga', 'yoga center'],
                'salon' => ['salons', 'beauty', 'beauty parlor', 'beauty parlour', 'spa', 'spas', 'barber'],
                'garage' => ['garages', 'auto repair', 'automobile', 'automotive', 'service center', 'workshop'],
                'office' => ['offices', 'business', 'corporate', 'company', 'companies', 'firm', 'agency'],
                'factory' => ['factories', 'manufacturing', 'industry', 'industrial', 'plant', 'mill'],
                'market' => ['markets', 'marketplace', 'bazaar', 'bazaars', 'shopping complex', 'mall'],
                'temple' => ['temples', 'religious', 'worship', 'shrine', 'shrines', 'mandir'],
                'church' => ['churches', 'christian', 'chapel', 'chapels', 'cathedral'],
                'mosque' => ['mosques', 'islamic', 'masjid', 'masjids', 'dargah'],
                'petrol' => ['petrol pump', 'gas station', 'fuel station', 'bunk', 'petrol bunk'],
                'jewellery' => ['jewelry', 'jewellers', 'gold', 'ornaments'],
                'textile' => ['textiles', 'cloth', 'fabric', 'garments', 'clothing'],
                'electronics' => ['electronic', 'mobile', 'computer', 'appliances'],
                'construction' => ['builder', 'contractor', 'civil', 'architecture'],
                'transport' => ['transportation', 'logistics', 'courier', 'travel', 'travels']
            ];
            
            // Check if input category matches any mapping
            foreach ($category_mappings as $base_category => $variations) {
                if ($category === $base_category || in_array($category, $variations)) {
                    // Check if base category exists in database
                    if (in_array($base_category, $db_categories)) {
                        return $base_category;
                    }
                    // Check if any variation exists in database
                    foreach ($variations as $variation) {
                        if (in_array($variation, $db_categories)) {
                            return $variation;
                        }
                    }
                }
            }
            
            // Fuzzy matching
            $best_match = null;
            $best_score = 0;
            
            foreach ($db_categories as $db_cat) {
                $score = 0;
                
                if (strpos($db_cat, $category) !== false || strpos($category, $db_cat) !== false) {
                    $score = 10;
                }
                
                $similarity = 0;
                similar_text($category, $db_cat, $similarity);
                $score += ($similarity / 100) * 5;
                
                $distance = levenshtein($category, $db_cat);
                if ($distance <= 2 && strlen($category) > 3) {
                    $score += (3 - $distance);
                }
                
                if ($score > $best_score && $score > 3) {
                    $best_score = $score;
                    $best_match = $db_cat;
                }
            }
            
            if ($best_match) {
                return $best_match;
            }
            
            return $category;
            
        } catch (Exception $e) {
            error_log("Error in normalizeCategoryForSearch: " . $e->getMessage());
            return $category;
        }
    }

    // New method for city normalization
    private function normalizeCityForSearch($city) {
        try {
            $city = trim(strtolower($city));
            if (empty($city)) return $city;
            
            // Get all unique cities from the database for this user's scope
            $sql_where = "";
            $sql_params = [];
            
            if (!$this->is_superadmin) {
                $sql_where = "WHERE team_id = ?";
                $sql_params[] = $this->team_id;
            }
            
            $stmt = $this->pdo->prepare("SELECT DISTINCT LOWER(city) as city FROM businesses {$sql_where}");
            $stmt->execute($sql_params);
            $db_cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Direct match first
            if (in_array($city, $db_cities)) {
                return $city;
            }
            
            // City name variations and common aliases for Indian cities
            $city_mappings = [
                'chennai' => ['madras', 'chennai city'],
                'mumbai' => ['bombay', 'mumbai city'],
                'kolkata' => ['calcutta', 'kolkata city'],
                'bengaluru' => ['bangalore', 'bengaluru city', 'bangalore city'],
                'hyderabad' => ['hyderabad city', 'secunderabad'],
                'pune' => ['poona', 'pune city'],
                'delhi' => ['new delhi', 'delhi city'],
                'ahmedabad' => ['amdavad', 'ahmedabad city'],
                'kochi' => ['cochin', 'kochi city'],
                'thiruvananthapuram' => ['trivandrum', 'tvm']
            ];
            
            // Check if input city matches any mapping
            foreach ($city_mappings as $base_city => $variations) {
                if ($city === $base_city || in_array($city, $variations)) {
                    if (in_array($base_city, $db_cities)) {
                        return $base_city;
                    }
                    // Check if any variation exists in database
                    foreach ($variations as $variation) {
                        if (in_array($variation, $db_cities)) {
                            return $variation;
                        }
                    }
                }
            }
            
            // Fuzzy matching for cities
            $best_match = null;
            $best_score = 0;
            
            foreach ($db_cities as $db_city) {
                $score = 0;
                
                // Exact substring match gets highest priority
                if (strpos($db_city, $city) !== false || strpos($city, $db_city) !== false) {
                    $score = 10;
                }
                
                // Similar length and character matching
                $similarity = 0;
                similar_text($city, $db_city, $similarity);
                $score += ($similarity / 100) * 5;
                
                // Levenshtein distance for close matches
                $distance = levenshtein($city, $db_city);
                if ($distance <= 2 && strlen($city) > 3) {
                    $score += (3 - $distance);
                }
                
                if ($score > $best_score && $score > 3) {
                    $best_score = $score;
                    $best_match = $db_city;
                }
            }
            
            if ($best_match) {
                return $best_match;
            }
            
            return $city;
            
        } catch (Exception $e) {
            error_log("Error in normalizeCityForSearch: " . $e->getMessage());
            return $city;
        }
    }
}