<svg viewBox="0 0 950 720" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <style>
      .box { fill: #F9FAFB; stroke: #0F172A; stroke-width: 3; rx: 12; }
      .title { font-family: 'Open Sans', Arial, sans-serif; font-size: 16px; font-weight: 700; fill: #111827; }
      .subtitle { font-family: 'Open Sans', Arial, sans-serif; font-size: 12px; fill: #374151; }
      .arrow { stroke: #111827; stroke-width: 2.5; fill: none; marker-end: url(#arrowhead); }
      .label { font-family: 'Open Sans', Arial, sans-serif; font-size: 11px; fill: #111827; }
      .smalltext { font-family: 'Open Sans', Arial, sans-serif; font-size: 10px; fill: #374151; }
      .module { font-size: 10px; font-weight: 600; fill: white; }
    </style>
    <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
      <polygon points="0 0, 10 3, 0 6" fill="#111827" />
    </marker>
  </defs>

  <!-- Web Server -->
  <rect x="70" y="40" width="210" height="115" class="box"/>
  <text x="175" y="65" text-anchor="middle" class="title">Web Server</text>
  <circle cx="125" cy="90" r="16" fill="#4A90E2"/>
  <text x="125" y="95" text-anchor="middle" fill="white" font-size="12" font-weight="bold">PHP</text>
  <rect x="175" y="75" width="70" height="32" rx="6" fill="#6BBF59"/>
  <text x="210" y="96" text-anchor="middle" fill="white" font-size="11" font-weight="bold">MySQL</text>
  <text x="175" y="122" text-anchor="middle" class="subtitle">Backend Logic</text>

  <!-- Security & Auth -->
  <rect x="360" y="40" width="230" height="115" class="box"/>
  <text x="475" y="65" text-anchor="middle" class="title">Security & Auth</text>
  <rect x="375" y="80" width="52" height="38" rx="6" fill="#E63946"/>
  <text x="401" y="101" text-anchor="middle" fill="white" class="smalltext">SSL</text>
  <circle cx="450" cy="97" r="18" fill="#4A90E2"/>
  <text x="450" y="102" text-anchor="middle" fill="white" class="smalltext">OAuth</text>
  <rect x="490" y="80" width="55" height="38" rx="6" fill="#6BBF59"/>
  <text x="518" y="101" text-anchor="middle" fill="white" class="smalltext">2FA</text>
  <text x="475" y="125" text-anchor="middle" class="subtitle">Secure Gateway</text>

  <!-- Users -->
  <rect x="70" y="200" width="210" height="115" class="box"/>
  <text x="175" y="225" text-anchor="middle" class="title">Users</text>
  <rect x="95" y="240" width="60" height="45" rx="8" fill="#9CA3AF"/>
  <text x="125" y="265" text-anchor="middle" font-size="22">💻</text>
  <text x="125" y="283" text-anchor="middle" class="subtitle">Desktop</text>
  <rect x="175" y="240" width="60" height="45" rx="8" fill="#9CA3AF"/>
  <text x="205" y="265" text-anchor="middle" font-size="22">📱</text>
  <text x="205" y="283" text-anchor="middle" class="subtitle">Mobile</text>

  <!-- StudyBuddy Core -->
  <rect x="320" y="190" width="320" height="165" class="box"/>
  <text x="480" y="220" text-anchor="middle" class="title" font-size="18">STUDYBUDDY</text>
  <text x="480" y="242" text-anchor="middle" class="subtitle">Core Application</text>

  <rect x="340" y="255" width="85" height="32" rx="6" fill="#4A90E2"/>
  <text x="382" y="275" text-anchor="middle" class="module">Matching</text>
  <rect x="435" y="255" width="85" height="32" rx="6" fill="#6BBF59"/>
  <text x="477" y="275" text-anchor="middle" class="module">Groups</text>
  <rect x="530" y="255" width="85" height="32" rx="6" fill="#F59E0B"/>
  <text x="572" y="275" text-anchor="middle" class="module">Chat</text>

  <rect x="340" y="295" width="85" height="32" rx="6" fill="#6366F1"/>
  <text x="382" y="315" text-anchor="middle" class="module">Scheduling</text>
  <rect x="435" y="295" width="85" height="32" rx="6" fill="#9CA3AF"/>
  <text x="477" y="315" text-anchor="middle" class="module">Analytics</text>
  <rect x="530" y="295" width="85" height="32" rx="6" fill="#EF4444"/>
  <text x="572" y="315" text-anchor="middle" class="module">Timer</text>

  <!-- 3rd-party Integrations -->
  <rect x="670" y="190" width="190" height="165" class="box"/>
  <text x="765" y="215" text-anchor="middle" class="title">3rd-party</text>
  <text x="765" y="232" text-anchor="middle" class="title">Integrations</text>
  <circle cx="720" cy="265" r="22" fill="#4A90E2"/>
  <text x="720" y="271" text-anchor="middle" fill="white" font-size="15" font-weight="bold">G</text>
  <text x="765" y="267" class="subtitle">Google Calendar</text>
  <circle cx="720" cy="312" r="22" fill="#EF4444"/>
  <text x="720" y="318" text-anchor="middle" fill="white" font-size="16">▶</text>
  <text x="765" y="308" class="subtitle">Video Call</text>
  <text x="765" y="323" class="subtitle">(Future)</text>

  <!-- Database Layer -->
  <rect x="70" y="415" width="790" height="230" class="box"/>
  <text x="465" y="445" text-anchor="middle" class="title">Database Layer</text>

  <!-- MySQL DB -->
  <g transform="translate(110,475)">
    <rect width="130" height="130" rx="10" fill="#E5E7EB" stroke="#0F172A" stroke-width="2.5"/>
    <text x="65" y="28" text-anchor="middle" font-weight="bold" fill="#111827">MySQL DB</text>
    <text x="65" y="55" text-anchor="middle" class="smalltext">Users • Groups</text>
    <text x="65" y="72" text-anchor="middle" class="smalltext">Messages • Sessions</text>
    <text x="65" y="89" text-anchor="middle" class="smalltext">Files • Achievements</text>
  </g>

  <!-- Data Processing -->
  <g transform="translate(330,475)">
    <rect width="160" height="130" rx="10" fill="#F3F4F6" stroke="#4A90E2" stroke-width="2.8"/>
    <text x="80" y="28" text-anchor="middle" font-weight="bold" fill="#111827">Data Processing</text>
    <text x="80" y="55" text-anchor="middle" class="smalltext">Matching Algorithm</text>
    <text x="80" y="73" text-anchor="middle" class="smalltext">Gender Equity Logic</text>
    <text x="80" y="91" text-anchor="middle" class="smalltext">Analytics Engine</text>
    <text x="80" y="109" text-anchor="middle" class="smalltext">CRUD Operations</text>
  </g>

  <!-- Frontend Output -->
  <g transform="translate(560,475)">
    <rect width="160" height="130" rx="10" fill="#F3F4F6" stroke="#6BBF59" stroke-width="2.8"/>
    <text x="80" y="28" text-anchor="middle" font-weight="bold" fill="#111827">Frontend Output</text>
    <rect x="30" y="48" width="100" height="25" rx="5" fill="#4A90E2"/>
    <text x="80" y="65" text-anchor="middle" class="module">HTML/CSS/JS</text>
    <rect x="30" y="78" width="100" height="25" rx="5" fill="#6BBF59"/>
    <text x="80" y="95" text-anchor="middle" class="module">AJAX Updates</text>
    <rect x="30" y="108" width="100" height="25" rx="5" fill="#F59E0B"/>
    <text x="80" y="125" text-anchor="middle" class="module">Responsive UI</text>
  </g>

  <!-- Arrows -->
  <path d="M 280 95 L 360 95" class="arrow"/>
  <text x="320" y="80" text-anchor="middle" class="label">API Calls</text>

  <path d="M 475 155 L 475 190" class="arrow"/>
  <text x="470" y="170" text-anchor="end" class="label">Secure Gateway</text>

  <path d="M 640 270 L 670 270" class="arrow"/>
  <path d="M 480 355 L 480 415" class="arrow"/>
  <text x="470" y="375" text-anchor="end" class="label">Data Flow</text>

  <path d="M 280 250 L 320 250" class="arrow"/>
  <text x="300" y="235" text-anchor="middle" class="label">WiFi</text>

  <path d="M 175 155 L 175 200" class="arrow" stroke-dasharray="5,5"/>
  <text x="195" y="165" text-anchor="start" class="label" font-size="10">WebSocket</text>
  <text x="195" y="178" text-anchor="start" class="label" font-size="10">(Real-time)</text>

  <!-- Caption -->
  <text x="465" y="690" text-anchor="middle" class="title" font-size="17">
    Figure: Technical Architecture Diagram of StudyBuddy App
  </text>
</svg>