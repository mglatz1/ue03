<?php
/**
 * The PHP norm form is used to gather, validate and process form data in a flexible way.
 *
 * This file represents a well known process of gathering, validating and processing form data within a single
 * PHP structure. At the initial request, the form is shown and data can be entered. Once the form is submitted
 * the input is validated. If errors occurred they are being displayed together with the form (which already
 * contains correctly entered input). When the form was filled out correctly, the result is displayed.
 * The norm form also ensures that user input is sanitized in order to avoid cross site scripting.
 * To use this procedural version of norm form, simple change the parts you want to modify or extend. This will
 * most likely be @show_form(), @is_valid_form() and @process_form().
 *
 * @author Rimbert Rudisch-Sommer <rimbert.rudisch-sommer@fh-hagenberg.at>
 * @author Wolfgang Hochleitner <wolfgang.hochleitner@fh-hagenberg.at>
 * @author Martin Harrer <martin.harrer@fh-hagenberg.at>
 * @version 2017
 */

session_start();

require_once("variables.inc.php");

/**
 * Main "decision" function for the form processing. This decision tree uses is_form_submission() to check if the form
 * is being initially displayed or shown again after a form submission and either calls show_form() to display the form
 * or validate the received input in is_valid_form(). If validation failed, show_form() is called again. Possible
 * error messages can now be displayed. Once the submission was correct, process_form() is called where the data can be
 * processed as needed. Ultimately the form is shown again including the generated output.
 */
function norm_form()
{
    if (is_form_submission()) {
        if (is_valid_form()) {
            process_form();
        }
    }
    show_form();
}

/**
 * Checks if the current request was an initial one (thus using GET) or a recurring one after a form submission (where
 * POST was used).
 * @return bool Returns true if a form was submitted or false if it was an initial call.
 */
function is_form_submission(): bool
{
    return ($_SERVER["REQUEST_METHOD"] === "POST");
}

/**
 * Validates the form submission. The criteria for this example are non-empty fields for first and last name. These are
 * checked using is_empty_post_field() in two separate if-clauses. If a criterion is violated, an entry in
 * $error_messages is created. If no error messages where created, validation is seen as successful.
 * @return bool Returns true if validation was successful, otherwise false.
 */
function is_valid_form(): bool
{
    global $error_messages;

    /**
     * Checks if the input is a valid number between current lower and upper limit
     */
    $validation_result = filter_input(
        INPUT_POST,
        NUMBER,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => $_SESSION[LOWER_LIMIT], "max_range" => $_SESSION[UPPER_LIMIT]]]
    );

    if ($validation_result === false) {
        $error_messages[NUMBER] = "Please enter an integer between " . $_SESSION[LOWER_LIMIT] . " and " . $_SESSION[UPPER_LIMIT] . ".";
    }

    return !isset($error_messages);
}

/**
 * Convenience function to check if a form field is empty, thus contains only an empty string. This is preferred to
 * PHP's own empty() method which also defines inputs such as "0" as empty.
 * @param string $index The index in the superglobal $_POST array.
 * @return bool Returns true if the form field is empty, otherwise false.
 */
function is_empty_post_field($index): bool
{
    return (!isset($_POST[$index]) || strlen(trim($_POST[$index])) === 0);
}

/**
 * Business logic method used to process the data that was used after a successful validation. In this example the
 * received data is stored in the global variable @result and later displayed. In more complex scenarios this would
 * be the place to add things to a database or perform other tasks before displaying the data.
 */
function process_form()
{
    global $result;

    $_SESSION[GUESSES]++;

    if($_POST[NUMBER] < $_SESSION[NUMBER]) {
        $result["message"] = $_POST[NUMBER] . " was too low. Guess again.";
        $_SESSION[LOWER_LIMIT] = $_POST[NUMBER] + 1;
    } elseif ($_POST[NUMBER] > $_SESSION[NUMBER]){
        $result["message"] = $_POST[NUMBER] . " was too high. Guess again.";
        $_SESSION[UPPER_LIMIT] = $_POST[NUMBER] - 1;
    } else {
        $result["message"] = "Congratulations! You've guessed the number " . $_SESSION[NUMBER] . " after " . $_SESSION[GUESSES] . " attempts.";
        if ($_SESSION[GUESSES] < 5) {
            $result["message"] .= " That was fast!";
        } elseif ($_SESSION[GUESSES] < 10) {
            $result["message"] .= " That was okay.";
        } else {
            $result["message"] .= " That was bad. Try better next time.";
        }

        load_contents();
        add_entry();
        store_contents();
        printEntries();

        resetVars();
    }
}

/**
 * Used to display output. First, it generates output for error messages and a status message if those were set in
 * is_valid_form(). Then the form is displayed where this output is used.
 */
function show_form()
{
    generate_error_messages();
    generate_status_message();
    generate_result();
    display();
}

/**
 * Generates an HTML fragment containing all error messages that occurred while validating the form. This fragment is
 * then used in display() to show the error messages.
 */
function generate_error_messages()
{
    global $error_messages;
    global $error_fragment;

    if (isset($error_messages)) {
        $error_fragment = "<div class=\"Error\">" . PHP_EOL;
        $error_fragment .= "<ul class=\"Error-list\">" . PHP_EOL;
        foreach ($error_messages as $e) {
            $error_fragment .= "<li class=\"Error-listItem\">$e</li>" . PHP_EOL;
        }
        $error_fragment .= "</ul>" . PHP_EOL;
        $error_fragment .= "</div>" . PHP_EOL;
    }
}

/**
 * Generates an HTML fragment containing a status message if one was set in process_form(). This fragment is then used
 * in display() to show the status message.
 */
function generate_status_message()
{
    global $status_message;
    global $status_fragment;

    if (isset($status_message)) {
        $status_fragment = "<div class=\"Status\">" . PHP_EOL;
        $status_fragment .= "<p class=\"Status-message\"><i class=\"fa fa-check\"></i>$status_message</p>" . PHP_EOL;
        $status_fragment .= "</div>" . PHP_EOL;
    }
}

/**
 * Generates an HTML fragment containing the result if one was set in process_form(). This fragment is then used in
 * display() to show the status message.
 */
function generate_result()
{
    global $result;
    global $result_fragment;

    if (isset($result)) {
        foreach ($result as $key => $value) {
            $result_fragment .= "<div class=\"Container\">" . PHP_EOL;
            $result_fragment .= "<p class=\"Container-message\">$value</p>" . PHP_EOL;
            $result_fragment .= "</div>" . PHP_EOL;
        }
    }
}

/**
 * Loads all stored high_scores line by line if file exist
 */
function load_contents()
{
    if(file_exists(FILE)) {
        $fp = fopen(FILE, 'r');

        $index = 0;
        while (($line = fgets($fp)) !== false) {
            $_SESSION[HIGHSCORE][$index] = $line;
            $index++;
        }
    }
}

/**
 * Stores all high_scores + the new one into the file if it exists, if not it will create a new file
 */
function store_contents()
{
    if (!file_exists(FILE)) {
        $fp = fopen(FILE, "w");
    } else {
        $fp = fopen(FILE, "r+");
    }
    $lock = flock($fp, LOCK_EX);
    if ($lock) {
        ftruncate($fp, 0);
        foreach ($_SESSION[HIGHSCORE] as $line) {
            fwrite($fp, $line);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    load_contents();
}

/**
 * prints out all high_score in a table sorted by the lowest number of trys
 */
function printEntries()
{
    global $high_score_fragment;

    $high_score_fragment = "<h3 class=\"Section-heading\">High-Score</h3>";
    $high_score_fragment .= "<table class=\"Table u-tableW100\">" . PHP_EOL;
    $high_score_fragment .= "<thead>" . PHP_EOL;
    $high_score_fragment .= "<tr class=\"Table-row\">" . PHP_EOL;
    $high_score_fragment .= "<th class=\"Table-header\">Attempts</th>" . PHP_EOL;
    $high_score_fragment .= "<th class=\"Table-header\">Number</th>" . PHP_EOL;
    $high_score_fragment .= "<th class=\"Table-header\">Date</th>" . PHP_EOL;
    $high_score_fragment .= "<th class=\"Table-header\">Time</th>" . PHP_EOL;
    $high_score_fragment .= "</tr>" . PHP_EOL;
    $high_score_fragment .= "</thead>" . PHP_EOL;
    $high_score_fragment .= "<tbody>" . PHP_EOL;

    $index = 0;
    while($index < sizeof($_SESSION[HIGHSCORE])) {
        $high = explode("|", $_SESSION[HIGHSCORE][$index]);
        $high_score_fragment .= "<tr class=\"Table-row\">" . PHP_EOL;
        $high_score_fragment .= "<td class=\"Table-data\">$high[0]</td>" . PHP_EOL;
        $high_score_fragment .= "<td class=\"Table-data\">$high[1]</td>" . PHP_EOL;
        $high_score_fragment .= "<td class=\"Table-data\">$high[2]</td>" . PHP_EOL;
        $high_score_fragment .= "<td class=\"Table-data\">$high[3]</td>" . PHP_EOL;
        $high_score_fragment .= "</tr>" . PHP_EOL;
        $index++;
    }
    $high_score_fragment .= "</tbody>" . PHP_EOL;
    $high_score_fragment .= "</table>" . PHP_EOL;
}

/**
 * Adds the last played game in the global variable and sorts it by the lowest number of trys
 */
function add_entry()
{
    array_push($_SESSION[HIGHSCORE], $_SESSION[GUESSES] . '|' . $_SESSION[NUMBER] . '|' . date("d.m.Y") . '|' . date("H:i:s") . PHP_EOL);
    natsort($_SESSION[HIGHSCORE]);
}

/**
 * Resets all current variables and determines a new random number in order to start a new game
 */
function resetVars()
{
    $_SESSION[NUMBER] = mt_rand(MIN, MAX);
    $_SESSION[LOWER_LIMIT] = MIN;
    $_SESSION[UPPER_LIMIT] = MAX;
    $_SESSION[GUESSES] = 0;
    $_SESSION[HIGHSCORE] = [];
}

/**
 * Creates the full HTML page (form and output) and displays it. Fragments for error messages, status message and result
 * are inserted if available.
 */
function display()
{
    global $error_fragment;
    global $status_fragment;
    global $result_fragment;
    global $high_score_fragment;

    if(!is_form_submission()) {
        $_SESSION[NUMBER] = mt_rand(MIN, MAX);
        $_SESSION[LOWER_LIMIT] = MIN;
        $_SESSION[UPPER_LIMIT] = MAX;
        $_SESSION[GUESSES] = 0;
        $_SESSION[HIGHSCORE] = [];
    }

    $script_name = $_SERVER["SCRIPT_NAME"];
    $number_key = NUMBER;
    $lower_limit = $_SESSION[LOWER_LIMIT];
    $upper_limit = $_SESSION[UPPER_LIMIT];
    $guesses = $_SESSION[GUESSES];

    // The HEREDOC syntax is used to store the markup for the whole page in a string.
    $page = <<<PAGE
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>norm_form Example</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,300,700">
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="../vendor/normform/css/main.css">
        </head>
        <body class="Site">
        <header class="Site-header">
            <div class="Header Header--small">
                <div class="Header-titles">
                    <h1 class="Header-title"><i class="fa fa-file-text-o" aria-hidden="true"></i>norm_form</h1>
                    <p class="Header-subtitle">Example Implementation</p>
                </div>
            </div>
        </header>
        <main class="Site-content">
            <section class="Section">
                <div class="Container">
                    <h2 class="Section-heading">Enter a Number between $lower_limit and $upper_limit</h2>
                    $error_fragment
                    $status_fragment
                    <form action="$script_name" method="post">
                        <div class="Grid Grid--gutters">
                            <div class="InputCombo Grid-full">
                                <label for="$number_key" class="InputCombo-label">Your Guess*:</label>
                                <input type="text" id="$number_key" name="$number_key" class="InputCombo-field">
                            </div>
                            <div class="Grid-full">
                                <button type="submit" class="Button">Guess</button>
                            </div>
                        </div>
                    </form>        
                </div>
            </section>
            <section class="Section">
                <div class="Container">
                    <h2 class="Section-heading">Guessing results</h2>
                    <p>Attempts: $guesses</p>
                    $result_fragment
                    $high_score_fragment
                    <p><a href="index.php">Start new game</a></p>
                </div>
            </section>
        </main> 
        <footer class="Site-footer">
            <div class="Footer Footer--small">
                <p class="Footer-credits">Created and maintained by 
                    <a href="mailto:martin.harrer@fh-hagenberg.at">Martin Harrer</a> and 
                    <a href="mailto:wolfgang.hochleitner@fh-hagenberg.at">Wolfgang Hochleitner</a>.
                </p>
                    <p class="Footer-version"><i class="fa fa-file-text-o" aria-hidden="true"></i>
                    <a href="https://github.com/Digital-Media/normform">norm_form Example Version 2017</a>
                </p>
            </div>
        </footer>
        </body>
        </html>
PAGE;

    // Then this string is being displayed using echo.
    echo $page;
}

/**
 * This function is responsible for filling in correct values in a resubmitted form. It checks if a value for the
 * specified form field already exists in $_POST. If yes, this value is sanitized and returned (to avoid cross site
 * scripting). If not, an empty string is returned. Additionally, unnecessary white spaces are removed.
 * @param string $name The name of the form field that should be processed.
 * @return string Returns the sanitized value in $_POST or an empty string.
 */
function autofill_form_field(string $name): string
{
    return isset($_POST[$name]) ? trim(sanitize_filter($_POST[$name])) : "";
}

/**
 * Filters unnecessary HTML tags from a string and returns the sanitized text.
 * @param string $str The input string with possible harmful content.
 * @return string The sanitized string that can be safely used.
 */
function sanitize_filter(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5);
}

// --- This is the main call of the norm form process

norm_form();
