<h1>Simba CRM - AI-Powered CRM for Indian SMEs | SAAS </h1>
<h1>Simba CRM 🦁</h1>
        <p class="tagline">The AI-Powered CRM for Indian SMEs | SAAS </p>
    </div>
<img src="https://github.com/Agrisakthi-india/Simba-CRM/blob/main/simba%20crm.png">
<img src="https://github.com/Agrisakthi-india/Simba-CRM/blob/main/simba%20crm2.png">
<img src="https://github.com/Agrisakthi-india/Simba-CRM/blob/main/simba%20crm3.png">
<img src="https://github.com/Agrisakthi-india/Simba-CRM/blob/main/simba%20crm4.png">
<img src="https://github.com/Agrisakthi-india/Simba-CRM/blob/main/simba%20crm5.png">




 <p>
        <strong>Simba CRM is an intelligent Customer Relationship Management system designed from the ground up to empower Small and Medium Enterprises (SMEs) in India.</strong> It goes beyond simple data storage by integrating a powerful AI Decision Engine, powered by Google's Gemini 1.5 Pro, to transform your business data into actionable, high-quality leads.
    </p>
    <p>
        Finding the right customers is the biggest challenge for any growing business. Simba CRM tackles this head-on by automating lead identification, scoring, and initial outreach, allowing you to focus on what you do best: closing deals and building relationships.
    </p>

  <hr>

   <h2>✨ Key Features</h2>
    <p>Simba CRM is packed with features designed for efficiency and growth.</p>

  <h3>Core CRM Functionality</h3>
    <ul>
        <li><strong>Business & Lead Management:</strong> Maintain a clean, centralized database of all your potential and active leads.</li>
        <li><strong>Advanced Filtering & Search:</strong> Effortlessly segment your business database by State, District, City, Category, Rating, and more to pinpoint your ideal customer profile.</li>
        <li><strong>Dependent Location Dropdowns:</strong> A smart and intuitive UI for drilling down into specific geographical areas across India.</li>
        <li><strong>Secure & Scalable:</strong> Built on a reliable PHP & MySQL stack, ensuring your data is safe and the system can grow with you.</li>
    </ul>

  <h3>🧠 The AI Decision Engine (Powered by Gemini 1.5 Pro)</h3>
    <p>This is where Simba CRM truly roars. Our AI engine analyzes your business prospects and delivers a ranked list of high-potential conversion candidates.</p>
    <ul>
        <li><strong>Intelligent Lead Scoring:</strong> A sophisticated algorithm scores businesses based on multiple data points like rating, profile completeness, contact information, and location desirability.</li>
        <li><strong>AI-Powered Conversion Probability:</strong> Gemini 1.5 Pro analyzes each business profile to predict the likelihood of conversion, giving you a clear percentage to work with.</li>
        <li><strong>Strategic Lead Identification:</strong> Choose your lead generation strategy (Aggressive, Balanced, or Conservative) and let the AI find prospects that match your risk and effort appetite.</li>
        <li><strong>Automated Personalized Outreach:</strong> Instantly generate professional, personalized initial contact messages (perfect for WhatsApp) for your new leads, crafted by AI to maximize response rates.</li>
        <li><strong>Smart Follow-up Generation:</strong> For existing leads, the AI analyzes past interactions to generate context-aware follow-up messages, ensuring you never miss an opportunity.</li>
        <li><strong>Bulk Conversion:</strong> Convert dozens of AI-vetted prospects into active leads with a single click, complete with auto-generated intro messages.</li>
        <li><strong>Dynamic Score Re-evaluation:</strong> Keep your lead data fresh by having the AI periodically reassess lead scores based on new market trends and data.</li>
    </ul>

  <h2>⚙️ Technology Stack</h2>
    <ul>
        <li><strong>Backend:</strong> PHP 7.4+</li>
        <li><strong>Database:</strong> MySQL / MariaDB</li>
        <li><strong>Frontend:</strong> HTML5, CSS3, JavaScript (ES6)</li>
        <li><strong>UI Framework:</strong> Bootstrap 5</li>
        <li><strong>AI:</strong> Google Gemini 1.5 Pro API</li>
        <li><strong>Server:</strong> Apache / Nginx</li>
    </ul>

   <h2>🚀 Getting Started</h2>
    <p>Follow these steps to get your own instance of Simba CRM up and running.</p>
    
   <h3>Prerequisites</h3>
    <ul>
        <li>A web server with PHP 7.4+</li>
        <li>A MySQL or MariaDB database</li>
        <li>A Google AI Studio account to get a <strong>Gemini API Key</strong></li>
    </ul>

  <h3>Installation</h3>
    <ol>
        <li>
            <strong>Clone the repository:</strong>
            <pre><code>git clone https://github.com/Agrisakthi-india/Simba-CRM.git
cd simba-crm</code></pre>
        </li>
        <li>
            <strong>Database Setup:</strong>
            <ul>
                <li>Create a new database in your MySQL server.</li>
                <li>Import the <code>database_schema.sql</code> file into your new database. This will create all the necessary tables.</li>
            </ul>
        </li>
        <li>
            <strong>Configuration:</strong>
            <ul>
                <li>Rename the <code>sme_config.sample.php</code> file to <code>sme_config.php</code>.</li>
                <li>Open <code>sme_config.php</code> and fill in your database credentials (<code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code>).</li>
            </ul>
        </li>
        <li>
            <strong>Add Your Gemini API Key:</strong>
            <ul>
                <li>This is the most important step for the AI features.</li>
                <li>Log into your application and navigate to the company profile page.</li>
                <li>Enter your <strong>Gemini API Key</strong> in the designated field and save. The AI will not work without it.</li>
            </ul>
        </li>
        <li>
            <strong>Deploy:</strong>
            <ul>
                <li>Upload the entire project folder to your web server.</li>
                <li>Navigate to the URL in your browser and log in!</li>
            </ul>
        </li>
    </ol>

  <h2>💡 Usage</h2>
    <ol>
        <li><strong>Populate your <code>businesses</code> table</strong> with potential customer data.</li>
        <li>Navigate to the <strong>AI Analyzer</strong> page (<code>ai_analyzer_page.php</code>).</li>
        <li>Use the filters to narrow down the market segment you want to analyze.</li>
        <li>Select your conversion strategy (e.g., "Balanced").</li>
        <li>Click <strong>"Analyze Businesses with AI"</strong>.</li>
        <li>Review the AI-recommended candidates, their scores, and key factors.</li>
        <li>Select the prospects you want to pursue and click <strong>"Convert Selected"</strong>.</li>
        <li>Congratulations! You now have a set of high-quality leads with initial contact messages ready to go.</li>
    </ol>
    
  <h2>📄 License & Attribution</h2>
    <p>
        Simba CRM is open-sourced software licensed under the <strong>GNU Affero General Public License v3 (AGPLv3)</strong>. You can view the full license in the <code>LICENSE</code> file.
    </p>
    <p>
        In addition to the terms of the AGPLv3, we require the following attribution to be displayed in any and all public-facing instances of this software, whether modified or unmodified:
    </p>
    <blockquote>
        <strong>You must retain the "Powered by Simba CRM" link in the footer of the application, pointing back to <code>https://agrisakthi.com</code>.</strong>
    </blockquote>
    <p>
        This attribution is a core part of our open-source model, allowing us to continue development and support for this project. Removal of this attribution is a violation of the licensing terms.
    </p>

  <h2>❤️ Support & Donations</h2>
    <p>
        Simba CRM is a free and open-source project, and we plan to keep it that way. Development requires significant time and resources. If you find this CRM useful for your business, please consider supporting its continued development through a donation.
    </p>
    <p>Your support helps cover:</p>
    <ul>
        <li>Server and hosting costs</li>
        <li>Further AI integration and development</li>
        <li>Adding new features requested by the community</li>
    </ul>
    <p>
        <strong>You can make a one-time or recurring donation via Razorpay.</strong>
    </p>
    <p style="text-align: center;">
        <a href="https://razorpay.me/@agrisakthimarketplaceprivatel" target="_blank" style="display: inline-block; padding: 12px 24px; background-color: #02042b; color: #fff; border-radius: 5px; font-weight: bold; text-decoration: none;">
            💳 Donate with Razorpay
        </a>
    </p>
    <p>
        We are incredibly grateful for any support you can provide!
    </p>

  <h2>🤝 Contributing</h2>
    <p>
        Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are <strong>greatly appreciated</strong>.
    </p>
    <p>
        Please feel free to fork the repo, create a feature branch, and submit a pull request.
    </p>

  <h2>📧 Contact</h2>
    <p>
        Agrisakthi - <a href="https://agrisakthi.com">https://agrisakthi.com</a>
    </p>
    <p>
        Project Link: <a href="https://github.com/Agrisakthi-india/Simba-CRM">https://github.com/Agrisakthi-india/Simba-CRM</a>
    </p>
