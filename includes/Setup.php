<?php

/**
 * Print setup instructions.
 */
class Setup {

    /**
     * String with setup instructions.
     */
    private $setup_instructions = <<<EOT
<html>
<head>
<title>MaSIS Setup</title>
</head>
<body>
<h1>MaSIS Setup</h1>
<p>File settings.php was not found.</p>
<ol>
    <li>Copy it from <code>config/settings.php</code>
    to the web site root folder.</li>
    <li>Create a PostgreSQL database using database.sql from the config directory.</li>
    <li>Open settings.php in a text editor and change the settings where
    necessary.</li>
    <li>Finally, reload this page.</li>
</ol>
</body>
</html>
EOT;

    /**
     * Constructor.
     *
     * Prints instructions for setting up MaSIS.
     */
    public function __construct() {
        print $this->setup_instructions;
    }
}
