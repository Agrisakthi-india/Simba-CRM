-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 31, 2025 at 11:54 AM
-- Server version: 10.6.23-MariaDB-cll-lve
-- PHP Version: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agrisakthiin_CRM`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_interactions`
--

CREATE TABLE `ai_interactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `interaction_type` enum('business_analysis','lead_scoring','message_generation','follow_up') NOT NULL,
  `prompt_tokens` int(11) DEFAULT 0,
  `completion_tokens` int(11) DEFAULT 0,
  `total_tokens` int(11) DEFAULT 0,
  `response_time_ms` int(11) DEFAULT 0,
  `success` tinyint(1) DEFAULT 1,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contacts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contacts`)),
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rating` float DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `website` text DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`id`, `user_id`, `team_id`, `state`, `district`, `city`, `category`, `name`, `contact_person`, `contacts`, `address`, `description`, `rating`, `latitude`, `longitude`, `place_id`, `website`, `phone_number`, `email`) VALUES
(1, 3, 2, 'Tamilnadu', 'Ariyalur', 'Ariyalur', 'supermarket', 'NATIONAL SUPER MARKET', NULL, NULL, '36, Vellalar St, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', NULL, 3.9, 11.13702830, 79.07269050, 'ChIJowWkgHvfqjsRgx02rgLEkK4', NULL, NULL, NULL),
(2, 3, 2, 'Tamilnadu', 'Ariyalur', 'Ariyalur', 'supermarket', 'Reliance SMART Superstore', NULL, '[{\"name\":\"\",\"role\":\"Primary\",\"phone\":\"+91 1800 891 0001\",\"email\":\"\"}]', 'GF FF, No 11 11A, PGM Complex, near Kamarajar Thidal, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', NULL, 3.9, 11.13373850, 79.07142560, 'ChIJs2SYG3vfqjsRpGJXcAzCFQs', 'https://stores.reliancesmartbazaar.com/reliance-smart-superstore-hypermarket-oppilathamman-kovil-street-ariyalur-283604/Home', '+91 1800 891 0001', NULL),
(3, 3, 2, 'Tamilnadu', 'Ariyalur', 'Ariyalur', 'supermarket', 'VASANTHAM SUPER MARKET', NULL, '[{\"name\":\"\",\"role\":\"Primary\",\"phone\":\"+91 70942 63638\",\"email\":\"\"}]', 'Subramaniya Nagar, Ariyalur, Tamil Nadu 621704, India', NULL, 4.3, 11.14392160, 79.07776870, 'ChIJ64NNimUhqzsRUvuJjV6wNuA', NULL, '+91 70942 63638', NULL),
(4, 3, 2, 'Tamilnadu', 'Ariyalur', 'Ariyalur', 'supermarket', 'Venkateswara Super Market', NULL, '[{\"name\":\"\",\"role\":\"Primary\",\"phone\":\"+91 80567 85558\",\"email\":\"\"}]', '17A, Ramalinga mudaliyar street, Ethraj Nagar, Ariyalur, Tamil Nadu 621704, India', NULL, 4.4, 11.13731420, 79.07074780, 'ChIJVxj5eAnfqjsRG922fppRqwI', NULL, '+91 80567 85558', NULL),
-- --------------------------------------------------------

--
-- Table structure for table `business_analysis`
--

CREATE TABLE `business_analysis` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `analysis_type` enum('lead_scoring','market_potential','conversion_analysis') NOT NULL,
  `analysis_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Detailed analysis results from AI' CHECK (json_valid(`analysis_data`)),
  `score` decimal(5,2) NOT NULL COMMENT 'Overall analysis score',
  `confidence_level` decimal(3,2) DEFAULT 0.85 COMMENT 'AI confidence in analysis (0-1)',
  `analyzed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When this analysis becomes stale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_leads`
--

CREATE TABLE `campaign_leads` (
  `campaign_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `conversion_score` decimal(5,2) NOT NULL,
  `converted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_profile`
--

CREATE TABLE `company_profile` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `services_description` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `gemini_api_key` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_profile`
--

INSERT INTO `company_profile` (`id`, `user_id`, `company_name`, `services_description`, `contact_person`, `gemini_api_key`, `updated_at`) VALUES
(1, 3, 'Agrisakthi', 'Agrisakthi provide all kind of grocery items to all, fresh and products', 'editor.vivasayam@gmail.com', 'AIzaxxxxxxxxxx', '2025-08-24 09:06:49'),
(2, 1, 'Visualmedia Technologies', 'Website Design & Development, Digital Marketing , SEO Services, and Technical Support for all there Web Realted', 'murali@visualmediatech.com', 'xxxxx@1234', '2025-08-07 08:56:44'),

-- --------------------------------------------------------

--
-- Table structure for table `conversion_campaigns`
--

CREATE TABLE `conversion_campaigns` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `strategy` enum('aggressive','balanced','conservative') NOT NULL,
  `filter_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Filters used for business selection' CHECK (json_valid(`filter_criteria`)),
  `businesses_analyzed` int(11) DEFAULT 0,
  `leads_converted` int(11) DEFAULT 0,
  `total_score` decimal(8,2) DEFAULT 0.00 COMMENT 'Sum of all lead scores in campaign',
  `campaign_status` enum('running','completed','paused','failed') DEFAULT 'running',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `field_visits`
--

CREATE TABLE `field_visits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `planned_date` date NOT NULL,
  `visit_status` enum('Planned','Visited','Cancelled') NOT NULL DEFAULT 'Planned',
  `check_in_time` datetime DEFAULT NULL,
  `check_in_latitude` decimal(10,8) DEFAULT NULL,
  `check_in_longitude` decimal(11,8) DEFAULT NULL,
  `visit_notes` text DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `assigned_user_id` int(11) NOT NULL,
  `status` enum('Follow-up','Connected','Committed','Not Interested') NOT NULL DEFAULT 'Follow-up',
  `state` varchar(100) DEFAULT NULL,
  `ai_score` int(3) DEFAULT NULL,
  `ai_reasoning` varchar(255) DEFAULT NULL,
  `ai_icp` text DEFAULT NULL,
  `ai_persona` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lead_score` decimal(5,2) DEFAULT 0.00 COMMENT 'AI calculated lead score (0-100)',
  `conversion_probability` decimal(5,2) DEFAULT 0.00 COMMENT 'AI predicted conversion probability (0-100)',
  `key_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of key factors contributing to score' CHECK (json_valid(`key_factors`)),
  `last_interaction` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Last interaction timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `company_id`, `user_id`, `team_id`, `assigned_user_id`, `status`, `state`, `ai_score`, `ai_reasoning`, `ai_icp`, `ai_persona`, `created_at`, `updated_at`, `lead_score`, `conversion_probability`, `key_factors`, `last_interaction`) VALUES
(7, 471, 3, 2, 3, 'Follow-up', 'Tamilnadu', 90, 'Muthu Super Market, categorized as a supermarket, strongly aligns with Agrisakthi\'s service offerings to grocery shops of all sizes.', NULL, NULL, '2025-08-02 13:14:22', '2025-08-02 17:03:31', 0.00, 0.00, NULL, '2025-08-07 15:00:14'),

-- --------------------------------------------------------

--
-- Table structure for table `lead_activities`
--

CREATE TABLE `lead_activities` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('Call','Email','WhatsApp','Meeting','Note') NOT NULL,
  `notes` text NOT NULL,
  `activity_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_activities`
--

INSERT INTO `lead_activities` (`id`, `lead_id`, `user_id`, `activity_type`, `notes`, `activity_date`) VALUES
(1, 9, 3, 'Call', 'trust i am. followup', '2025-08-02 14:55:52'),

-- --------------------------------------------------------

--
-- Table structure for table `lead_messages`
--

CREATE TABLE `lead_messages` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `message_type` enum('initial_contact','follow_up','nurture','custom') NOT NULL,
  `message_content` text NOT NULL,
  `sent_status` enum('generated','sent','delivered','failed') DEFAULT 'generated',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_messages`
--

INSERT INTO `lead_messages` (`id`, `lead_id`, `message_type`, `message_content`, `sent_status`, `sent_at`, `created_at`) VALUES
(1, 10, 'follow_up', 'Hi there! It\'s been a while since we last connected about 11 to 11 Family Restaurant. I wanted to reach out because we\'ve had great success helping restaurants businesses in Kulithalai. Are you still interested in exploring growth opportunities?', 'generated', NULL, '2025-08-16 04:36:13'),
(2, 17, 'follow_up', 'Hello! I hope you\'re doing well. Following up on our conversation about Amma Naana Supermarket Chennai. Given your presence in Chennai, I believe our solutions could significantly impact your supermarket business. Would you be available for a call this week?', 'generated', NULL, '2025-08-27 18:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `lead_tasks`
--

CREATE TABLE `lead_tasks` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `due_date` datetime NOT NULL,
  `status` enum('Pending','Completed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_tasks`
--

INSERT INTO `lead_tasks` (`id`, `lead_id`, `user_id`, `title`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 9, 3, 'followup', '2025-08-16 20:26:00', 'Pending', '2025-08-02 14:56:23', '2025-08-02 14:56:23'),
(2, 12, 3, 'call', '2025-08-11 10:10:00', 'Pending', '2025-08-06 06:26:02', '2025-08-06 06:26:02');

-- --------------------------------------------------------

--
-- Table structure for table `simba_queries`
--

CREATE TABLE `simba_queries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `user_query` text NOT NULL,
  `simba_intent` varchar(255) DEFAULT NULL,
  `simba_response` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `simba_queries`
--

INSERT INTO `simba_queries` (`id`, `user_id`, `team_id`, `user_query`, `simba_intent`, `simba_response`, `timestamp`) VALUES
(1, 3, 2, 'What are my overdue tasks?', 'query_tasks', 'Sorry, I couldn\'t retrieve your tasks right now. Please try again later.', '2025-08-06 16:43:16'),
(2, 3, 2, 'show hotels in salem', 'filter_leads', 'Here are your filtered results for: **Category: restaurants, City: Salem**\n\n[🔍 View Results](index.php?category=restaurants&city=Salem)\n\nThis will show all businesses matching your search criteria.', '2025-08-06 16:43:37'),
(3, 3, 2, 'Lead summary', 'filter_leads', 'Please specify search criteria like category, city, or status.', '2025-08-06 16:43:56'),
(4, 3, 2, 'lead summary about salem', 'filter_leads', 'Please specify search criteria like category, city, or status.', '2025-08-06 16:44:10'),
(5, 3, 2, 'How many follow-up leads do I have?', 'Command', '📊 **Lead Statistics for \'follow-up\' status:**\n\n• Total leads: **5**\n• Added this week: **5**\n• Average AI Score: **27/100**\n\n[📋 View All follow-up Leads](index.php?status=follow-up)', '2025-08-07 09:31:10'),
(6, 3, 2, 'Help me grow my business', 'General Chat', 'Let\'s strategize growing Growmax finech.  Since you provide invoice discounting for exporters, our focus will be on lead generation, conversion, and client retention.\n\n**Phase 1:  Lead Generation & Qualification (Short-Term)**\n\n* **Target Audience Refinement:** We need to better define your ideal client. Are you focusing on specific export industries?  Specific export volumes?  Geographic locations?  The more precise your targeting, the higher your conversion rate.  Let\'s brainstorm ideal client profiles together.\n\n* **Online Presence Enhancement:**\n    * **Website Audit:** Is your website optimized for search engines (SEO)?  Does it clearly explain your services and benefits?  Does it include compelling calls to action (CTAs)?  We need a strong online presence to attract leads organically.\n    * **LinkedIn Marketing:**  Create engaging content showcasing your expertise in invoice discounting for exporters.  Target relevant industry groups and professionals.\n    * **Paid Advertising (PPC):** Consider targeted Google Ads or LinkedIn Ads campaigns.  This allows you to reach potential clients directly.\n\n* **Networking & Outreach:**\n    * **Industry Events:** Identify and attend relevant trade shows and conferences to network with potential clients and partners.\n    * **Direct Outreach:**  Create a targeted email campaign to potential clients.  This requires a well-crafted message highlighting the value proposition of your services – specifically how invoice discounting solves their problems (cash flow management).\n\n**Phase 2: Conversion Optimization (Mid-Term)**\n\n* **Sales Process Improvement:** Map out your current sales process.  Identify bottlenecks and areas for improvement.  Consider implementing a CRM (if you don\'t already have one) to track leads, manage communications, and analyze sales performance.\n\n* **Value Proposition Enhancement:**  Clearly articulate the ROI of your services.  Quantify the benefits of invoice discounting for exporters – faster payments, improved cash flow, reduced financial risk.\n\n* **Case Studies & Testimonials:** Gather success stories from existing clients and showcase them prominently on your website and marketing materials.  Social proof builds trust and credibility.\n\n\n**Phase 3: Client Retention & Referral (Long-Term)**\n\n* **Client Relationship Management:**  Prioritize excellent client service.  Regular communication, proactive support, and understanding client needs are key to fostering long-term relationships.\n\n* **Referral Program:** Encourage existing clients to refer new business.  Incentivize referrals with discounts or other rewards.\n\n\n**Next Steps:**\n\n1. **Ideal Client Profile:** Let\'s define your ideal client – industry, export volume, location etc.\n2. **Website Audit:**  I need access to your website to perform a quick audit.\n3. **CRM Implementation:** Discuss your current CRM strategy, or if you need help choosing and implementing one.\n\n\nThis is a starting point.  We will continually refine our strategy based on data and performance.  Let\'s schedule a call to discuss these points in more detail.  What works best for you?', '2025-08-07 09:31:23'),
(7, 3, 2, 'kindly give my business based which is able to get business from this db please', 'General Chat', 'I need more information to understand what \"this db\" refers to.  Please specify the database or data source you\'re referencing.  To help me understand and provide relevant business strategies based on that data, please tell me:\n\n* **What is \"this db\"?**  Is it a spreadsheet, a customer relationship management (CRM) system, a market research report, or something else?\n* **What kind of data does it contain?**  Does it contain potential client information, market trends, competitor data, or something else?\n* **What is your goal?** Are you looking to identify potential clients, understand market trends, or something else entirely?\n\nOnce I have this information, I can provide you with a tailored strategy to leverage the database for business growth.', '2025-08-07 09:32:36'),
(8, 3, 2, 'kindly give my business based which is able to get business from this business', 'General Chat', 'To give you businesses that can benefit from Growmax Finech\'s invoice discounting services, I need to understand your target market more clearly.  However, I can offer some examples of businesses that *often* leverage invoice discounting:\n\n* **Export-focused SMEs (Small and Medium-sized Enterprises):**  These businesses often face cash flow challenges due to extended payment terms from international buyers.  Growmax Finech\'s services directly address this.  Focus on SMEs in industries with high export volumes like agriculture, manufacturing, and textiles.\n\n* **Businesses with large receivables:**  Companies with a high volume of outstanding invoices can use invoice discounting to access immediate capital and improve their liquidity.\n\n* **Startups and rapidly growing businesses:** These companies may lack the financial resources to wait for extended payment terms and can benefit from fast access to cash provided by invoice discounting.\n\n* **Businesses facing seasonal fluctuations in cash flow:** Invoice discounting can provide a consistent cash flow throughout the year, even during periods of low sales.\n\nTo refine this list, tell me:\n\n* **What are the typical sizes of the companies you currently work with?** (Revenue, number of employees)\n* **Which industries do your current clients belong to?**\n* **What geographic regions do you primarily service?**\n\nThe more detail you provide, the more specific and effective my recommendations will be.', '2025-08-07 09:32:58'),
(9, 3, 2, 'Startups and rapidly growing businesses:', 'General Chat', 'Startups and rapidly growing businesses are an excellent target market for Growmax Finech.  Their characteristic rapid expansion often outpaces their cash flow, making invoice discounting particularly valuable.  Here\'s a targeted approach:\n\n**Marketing & Outreach Strategy for Startups & Rapidly Growing Businesses:**\n\n* **Messaging:** Highlight the speed and ease of accessing capital. Emphasize how Growmax Finech can fuel their growth by providing immediate cash flow, allowing them to invest in expansion, marketing, or new hires without delaying payments to suppliers.  Use language emphasizing agility and scalability.\n\n* **Channels:**\n    * **Online Advertising (PPC):** Target keywords related to startup funding, growth capital, and cash flow management on platforms like Google Ads and LinkedIn Ads.\n    * **Content Marketing:** Create blog posts, case studies, and infographics showcasing the benefits of invoice discounting for startups – focusing on success stories and quantifiable results.\n    * **Industry Events & Networking:** Attend startup events, tech conferences, and pitch competitions to connect directly with potential clients.  Sponsor relevant events to increase brand visibility.\n    * **Partnerships:** Collaborate with venture capitalists, incubators, and accelerators to reach their portfolio companies.\n\n* **Value Proposition:**  Tailor your value proposition to their specific needs.  Focus on:\n    * **Speed of funding:**  Emphasize the quick turnaround time for accessing funds.\n    * **Flexibility:**  Highlight the adaptable nature of your services to suit varying business needs.\n    * **Scalability:**  Showcase how your services can scale alongside their growth.\n    * **Reduced administrative burden:**  Emphasize that you handle the complex process of invoice discounting, freeing up their time to focus on core business activities.\n\n\n* **Case Studies:**  Develop case studies specifically featuring startups and rapidly growing businesses that have successfully used your invoice discounting services to fuel their growth.  Quantify their success – e.g., \"Increased revenue by X% after implementing Growmax Finech\'s services.\"\n\n\nBy focusing your marketing and outreach on the specific challenges and opportunities faced by startups and rapidly growing businesses, you can significantly improve your lead generation and conversion rates.  Remember to track your results and adjust your strategy as needed.', '2025-08-07 09:33:19'),
(10, 3, 2, 'show krishnagiri hotels', 'General Chat', 'Editor, while I can\'t directly show you a list of Krishnagiri hotels (that requires a search engine),  I can help you understand how this seemingly unrelated request relates to your business growth.\n\nFinding hotels in Krishnagiri might relate to:\n\n* **Client Meetings:**  Are you planning meetings with potential clients or exporters based in Krishnagiri? If so, let\'s discuss strategies for optimizing those meetings to secure more invoice discounting business.  We need to ensure these meetings are highly productive and result in signed contracts.\n\n* **Employee Travel:** Do your employees need to travel to Krishnagiri for business purposes? We need to factor in travel costs and time management to maximize efficiency.  Let\'s explore options to minimize travel expenses while maintaining a strong professional presence.\n\n* **Market Research:** Are you considering expanding your services to businesses in Krishnagiri? Understanding the local market and competition is vital before making any significant expansion moves.  We\'ll develop a strategy for thorough market research before committing resources.\n\nTo help me advise you best, please tell me *why* you\'re looking for hotels in Krishnagiri.  Connecting this information to your business strategy at Growmax FinTech will allow me to give you more valuable and actionable advice.', '2025-08-07 09:51:36'),
(11, 3, 2, 'shoe me hotels in chennai', 'General Chat', 'I understand you\'re looking for hotels in Chennai.  Again, I can\'t directly provide that list. However, this is a crucial detail that helps me understand your business needs.\n\nTo leverage this information for Growmax FinTech\'s growth, I need context:\n\n* **Why Chennai Hotels?**  Are you planning a client meeting, a conference, or a team-building event in Chennai?  Knowing the *reason* behind your hotel search helps me tailor strategies to boost your business.\n\n* **Target Audience:** Are you focusing on exporters in Chennai? If so, let\'s refine your target market segmentation to maximize your outreach and conversion rates within the Chennai region.\n\n* **Budget:** Understanding your budget for travel and accommodation helps me determine the best cost-effective strategies for reaching your target market in Chennai.\n\nProviding more details will allow me to provide specific, actionable steps to grow Growmax FinTech.  For example, if you\'re planning client meetings, we can discuss crafting compelling presentations, negotiating favorable terms, and follow-up strategies to secure deals.', '2025-08-07 09:51:51'),
(12, 3, 2, 'Show me restaurants in Jayanagar', 'General Chat', 'While I can\'t directly show you a list of restaurants in Jayanagar (that\'s a task for a search engine), I want to ensure this information serves your business goals for Growmax FinTech.  Knowing you\'re looking at restaurants in Jayanagar implies one of the following:\n\n* **Networking:** Are you considering meeting potential clients or partners who frequent restaurants in Jayanagar?  Let\'s discuss how to leverage networking events or business lunches to generate leads and expand your business network.\n\n* **Employee Welfare:**  Do your employees reside in or frequently visit Jayanagar? Considering employee satisfaction is crucial for a healthy work environment and retaining talent. We can explore strategies to improve employee morale and productivity.\n\n* **Market Research (Indirectly):** The choice of restaurants in a particular area can indirectly provide insight into the local market and the types of businesses that are prevalent there.  This could be useful for identifying potential clients or understanding local consumer behavior.\n\n\nTo help me better assist you, please tell me *why* you need this information.  Connecting your search to a strategic business goal for Growmax FinTech will allow me to provide more effective business growth advice.', '2025-08-07 09:52:02'),
(13, 3, 2, 'Show me restaurants in Jayanagar', 'General Chat', 'I understand you\'re looking for restaurants in Jayanagar.  However, as a business growth consultant, I need to understand the *business context* of your request.  Simply providing a list of restaurants isn\'t helpful for Growmax FinTech\'s growth.\n\nTo make this information useful, please tell me:\n\n* **How does finding restaurants in Jayanagar relate to Growmax FinTech\'s success?**  Are you planning a client meeting? Are you exploring a new market segment? Do you need to treat your employees to a celebratory meal?\n\nOnce I understand the connection, I can provide strategic advice.  For example, if you\'re meeting a client, I can offer tips on choosing the right restaurant for a professional yet comfortable atmosphere. If you\'re expanding to a new market, understanding the local dining scene can inform your marketing strategies.\n\n\nPlease provide the context so I can assist you effectively in growing your business.', '2025-08-07 09:52:15'),
(17, 3, 2, 'show me top rated hotels in salem', 'General Chat', 'Editor, while I appreciate your request, finding top-rated hotels in Salem is outside the scope of my expertise as your business growth consultant.  My focus is on helping Growmax finech thrive.  Searching for hotels is best done through a dedicated travel website or search engine like Google, Expedia, or Booking.com.\n\nTo better assist *your* business, let\'s focus on Growmax finech.  Perhaps we could discuss:\n\n* **Improving your lead generation strategy for invoice discounting services:** Are you effectively reaching exporters who need your LCM and Non-LCM financing options?\n* **Optimizing your CRM system:** Are you tracking your leads and clients effectively?  Are you leveraging your CRM for sales forecasting and identifying opportunities for upselling or cross-selling?\n* **Expanding your market reach:** Are there untapped export markets where Growmax finech could offer its services?\n* **Developing a stronger online presence:** Is your website optimized for search engines (SEO)? Do you have effective marketing materials to showcase your unique value proposition?\n\nLet\'s schedule a brief call to discuss these crucial aspects of your business growth.  Which area would you like to prioritize first?', '2025-08-11 04:22:48'),
(18, 3, 2, 'How many follow-up leads do I have?', 'Command', '📊 **Lead Statistics for \'follow-up\' status:**\n\n• Total leads: **5**\n• Added this week: **3**\n• Average AI Score: **27/100**\n\n[📋 View All follow-up Leads](index.php?status=follow-up)', '2025-08-11 04:24:19'),
(19, 3, 2, 'Give me a business summary', 'Command', '📊 **Your Lead Summary:**\n\n• Total Leads: **6**\n• Follow-up: **5**\n• Committed: **0**\n• Not Interested: **0**\n• New This Week: **3**\n• Average Score: **38.3/100**\n', '2025-08-11 04:24:24'),
(20, 3, 2, 'How many follow-up leads do I have?', 'Command', '📊 **Lead Statistics for \'follow-up\' status:**\n\n• Total leads: **5**\n• Added this week: **3**\n• Average AI Score: **27/100**\n\n[📋 View All follow-up Leads](index.php?status=follow-up)', '2025-08-11 04:24:33'),
(21, 3, 2, 'find top reated hotels in chennai', 'General Chat', 'While I can\'t directly browse and provide real-time information like hotel ratings, I can suggest how to leverage this information to benefit Growmax Finech.  Finding top-rated hotels in Chennai isn\'t directly related to your invoice discounting business.  However, understanding your customer\'s needs and preferences is vital.\n\nTo connect this to Growmax Finech, consider:\n\n* **Networking opportunities:**  If you attend industry events or meet with potential clients in Chennai, knowing the best hotels allows you to suggest convenient and high-quality locations for meetings. This shows professionalism and consideration.\n\n* **Client relationship management:** If you have high-value clients in Chennai, recommending top-rated hotels could be a value-added service, strengthening your relationship.\n\n* **Targeted marketing:**  By knowing which hotels your target customer demographic favors, you can consider sponsoring events or placing targeted ads.\n\n**Actionable Steps:**\n\n1. **Use a reputable hotel booking site:**  Sites like TripAdvisor, Booking.com, or Expedia will allow you to filter by rating, price, location, etc.  Search for \"top-rated hotels in Chennai.\"\n\n2. **Identify your target client profile:**  What type of hotels do your ideal clients prefer? (Luxury, budget-friendly, business-oriented, etc.) This will help you focus your search.\n\n3. **Integrate into your CRM:**  Once you\'ve identified relevant hotels, consider adding this information to your CRM system (if you have one) to track and utilize this data for future client interactions.\n\n\nFocus on using this information strategically to enhance your client relationships and brand image. Remember, the goal is to grow *Growmax Finech*, and understanding your client\'s world, even indirectly, helps achieve that.', '2025-08-11 04:25:14'),
(22, 3, 2, 'show top rated hotels in chennai', 'General Chat', 'I cannot directly access and display real-time information, such as hotel ratings.  My function is to help you grow your business, Growmax Finech.  To find top-rated hotels in Chennai, I recommend using online travel agencies like:\n\n* **TripAdvisor:**  Known for user reviews and ratings.\n* **Booking.com:** Offers a wide selection and detailed information.\n* **Expedia:** Another popular site with various filters for your search.\n* **Google Hotels:**  Provides a convenient way to search and compare hotels based on ratings and prices.\n\n\nSearching on these sites will give you a much more accurate and up-to-date list of top-rated hotels in Chennai than I can provide. Remember to consider the type of hotel that would best serve your business needs (e.g., for client meetings, or if you are planning to visit Chennai yourself).', '2025-08-11 04:25:30'),
(23, 3, 2, 'find hotels in chennai', 'General Chat', 'I cannot directly search and display hotel listings. To find hotels in Chennai, I recommend using online travel agencies such as:\n\n* **Google Hotels:** A convenient search engine that aggregates listings from various sources.\n* **Booking.com:** Offers a broad selection and detailed hotel information.\n* **Expedia:** Another popular choice with various filters for customizing your search.\n* **TripAdvisor:** Provides user reviews and ratings to help you choose.\n\n\nThese websites allow you to filter your search by price, location, amenities, and guest ratings, helping you find the perfect hotel for your needs.  Remember to specify your dates of travel and any preferences when searching.  This information is not directly related to growing your business, Growmax Finech, but efficient travel planning can save you time and resources that can then be reinvested in your business.', '2025-08-11 04:26:58'),
(24, 3, 2, 'Give me a business summary', 'Command', '📊 **Your Lead Summary:**\n\n• Total Leads: **6**\n• Follow-up: **5**\n• Committed: **0**\n• Not Interested: **0**\n• New This Week: **3**\n• Average Score: **38.3/100**\n', '2025-08-11 04:37:08'),
(25, 3, 2, 'find hotels in chennai', 'General Chat', 'Finding hotels in Chennai is outside the scope of my role as your business growth consultant for Growmax FinTech.  My focus is on helping you increase invoice discounting for LCM and Non-LCM exporters.  Searching for hotels is best done through online travel agencies like Booking.com, Expedia, or MakeMyTrip.\n\nTo stay focused on growing Growmax FinTech, I suggest we instead concentrate on:\n\n* **Identifying and qualifying new leads:**  Are we effectively reaching exporters who need invoice discounting services? Let\'s review your current lead generation strategies.\n* **Improving conversion rates:**  Why aren\'t your leads converting into clients? Let\'s analyze your sales process and identify bottlenecks.\n* **Optimizing your CRM:**  Are you using a CRM to track leads, manage communication, and analyze your sales performance?  A well-maintained CRM is crucial for growth.\n* **Expanding your market reach:** Are there untapped markets or exporter segments we should target?\n\n\nLet\'s schedule a brief call to discuss these growth strategies.  Which time works best for you?', '2025-08-11 04:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `team_name` varchar(255) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `team_name`, `owner_id`, `created_at`) VALUES
(2, 'editor.vivasayam\'s Team', 3, '2025-08-02 12:23:20'),
(3, 'info\'s Team', 4, '2025-08-13 07:37:29'),

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `account_role` enum('admin','member') NOT NULL DEFAULT 'member',
  `is_superadmin` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `team_id`, `account_role`, `is_superadmin`, `is_approved`, `created_at`) VALUES
(1, 'murali@visualmxxx.com', '123@123', NULL, 'admin', 1, 1, '2025-08-02 12:14:16'),
(3, 'editor.vivaxxxxx@gmail.com', '123@123', 2, 'admin', 0, 1, '2025-08-02 12:23:20'),


--
-- Table structure for table `user_category_access`
--

CREATE TABLE `user_category_access` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_interactions`
--
ALTER TABLE `ai_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_user` (`user_id`),
  ADD KEY `idx_interaction_type` (`interaction_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_success` (`success`);

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_team_id` (`team_id`);

--
-- Indexes for table `business_analysis`
--
ALTER TABLE `business_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_business_user_type` (`business_id`,`user_id`,`analysis_type`),
  ADD KEY `idx_business_analysis_business` (`business_id`),
  ADD KEY `idx_business_analysis_user` (`user_id`),
  ADD KEY `idx_analysis_type` (`analysis_type`),
  ADD KEY `idx_score` (`score`),
  ADD KEY `idx_analyzed_at` (`analyzed_at`);

--
-- Indexes for table `campaign_leads`
--
ALTER TABLE `campaign_leads`
  ADD PRIMARY KEY (`campaign_id`,`lead_id`),
  ADD KEY `idx_campaign_leads_campaign` (`campaign_id`),
  ADD KEY `idx_campaign_leads_lead` (`lead_id`);

--
-- Indexes for table `company_profile`
--
ALTER TABLE `company_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `conversion_campaigns`
--
ALTER TABLE `conversion_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign_user` (`user_id`),
  ADD KEY `idx_campaign_status` (`campaign_status`),
  ADD KEY `idx_started_at` (`started_at`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `field_visits`
--
ALTER TABLE `field_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `idx_user_plan_date` (`user_id`,`planned_date`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_company_lead` (`team_id`,`company_id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_user_id` (`assigned_user_id`),
  ADD KEY `idx_lead_score` (`lead_score`),
  ADD KEY `idx_conversion_probability` (`conversion_probability`),
  ADD KEY `idx_last_interaction` (`last_interaction`),
  ADD KEY `idx_leads_ai_profiles` (`ai_icp`(100),`ai_persona`(100));

--
-- Indexes for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lead_messages`
--
ALTER TABLE `lead_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead_messages_lead_id` (`lead_id`),
  ADD KEY `idx_message_type` (`message_type`),
  ADD KEY `idx_sent_status` (`sent_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `lead_tasks`
--
ALTER TABLE `lead_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `idx_user_status_due` (`user_id`,`status`,`due_date`);

--
-- Indexes for table `simba_queries`
--
ALTER TABLE `simba_queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `user_category_access`
--
ALTER TABLE `user_category_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_category` (`user_id`,`category`),
  ADD KEY `team_id` (`team_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_interactions`
--
ALTER TABLE `ai_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15456;

--
-- AUTO_INCREMENT for table `business_analysis`
--
ALTER TABLE `business_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_profile`
--
ALTER TABLE `company_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `conversion_campaigns`
--
ALTER TABLE `conversion_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `field_visits`
--
ALTER TABLE `field_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `lead_activities`
--
ALTER TABLE `lead_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lead_messages`
--
ALTER TABLE `lead_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lead_tasks`
--
ALTER TABLE `lead_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `simba_queries`
--
ALTER TABLE `simba_queries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_category_access`
--
ALTER TABLE `user_category_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_interactions`
--
ALTER TABLE `ai_interactions`
  ADD CONSTRAINT `ai_interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `businesses_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_analysis`
--
ALTER TABLE `business_analysis`
  ADD CONSTRAINT `business_analysis_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_analysis_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_leads`
--
ALTER TABLE `campaign_leads`
  ADD CONSTRAINT `campaign_leads_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `conversion_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_leads_ibfk_2` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `company_profile`
--
ALTER TABLE `company_profile`
  ADD CONSTRAINT `company_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversion_campaigns`
--
ALTER TABLE `conversion_campaigns`
  ADD CONSTRAINT `conversion_campaigns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD CONSTRAINT `email_templates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_templates_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `field_visits`
--
ALTER TABLE `field_visits`
  ADD CONSTRAINT `field_visits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `field_visits_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `field_visits_ibfk_3` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leads_ibfk_3` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leads_ibfk_4` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD CONSTRAINT `lead_activities_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_messages`
--
ALTER TABLE `lead_messages`
  ADD CONSTRAINT `lead_messages_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_tasks`
--
ALTER TABLE `lead_tasks`
  ADD CONSTRAINT `lead_tasks_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_tasks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `simba_queries`
--
ALTER TABLE `simba_queries`
  ADD CONSTRAINT `simba_queries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `simba_queries_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_category_access`
--
ALTER TABLE `user_category_access`
  ADD CONSTRAINT `user_category_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_category_access_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
