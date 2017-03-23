<?php
session_start();
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

//// hier gehts los
    $validation_result = filter_input(
        INPUT_POST,
        NUMBER,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => $_SESSION[LOWER_LIMIT], "max_range" => $_SESSION[UPPER_LIMIT]]]
    );

    if ($validation_result === false) {
        $error_messages[NUMBER] = "Please enter an integer between " . $_SESSION[LOWER_LIMIT] . " and " . $_SESSION[UPPER_LIMIT] . ".";
    }
//*/ // hier hörts auf

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

    if($_POST[])
    {
        $_SESSION[LOWER_LIMIT] = $_POST[NUMBER] + 1;
    } elseif ($_POST[NUMBER] > $_SESSION[NUMBER]){
        $result["message"] = $_POST[NUMBER] . " was too high. Guess again.";
        $_SESSION[UPPER_LIMIT] = $_POST[NUMBER] - 1;
    } else {
        $result["message"] = "Congratulations! You've guessed the number " . $_SESSION[NUMBER] . " after " . $_SESSION[GUESSES] . "attempts.";
        if ($_SESSION[GUESSES] < 4) {
            $result["message"] .= " You did great!";
        } elseif ($_SESSION[GUESSES] < 8) {
            $result["message"] .= " That was okay.";
        } else {
            $result["message"] .= " You weren't so lucky this time.";
        }

        /**  load_contents(); */
        /**  add_entry(); */
        $result[HIGHSCORE] = $_SESSION[HIGHSCORE];
        /**  store_contents(); */
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
        $result_fragment = "<table class=\"Table u-tableW100\">" . PHP_EOL;
        $result_fragment .= "<colgroup span=\"2\" class=\"u-tableW50\"></colgroup>" . PHP_EOL;
        $result_fragment .= "<thead>" . PHP_EOL;
        $result_fragment .= "<tr class=\"Table-row\">" . PHP_EOL;
        $result_fragment .= "<th class=\"Table-header\">Key</th>" . PHP_EOL;
        $result_fragment .= "<th class=\"Table-header\">Value</th>" . PHP_EOL;
        $result_fragment .= "</tr>" . PHP_EOL;
        $result_fragment .= "</thead>" . PHP_EOL;
        $result_fragment .= "<tbody>" . PHP_EOL;
        foreach ($result as $key => $value) {
            $result_fragment .= "<tr class=\"Table-row\">" . PHP_EOL;
            $result_fragment .= "<td class=\"Table-data\">$key</td>" . PHP_EOL;
            $result_fragment .= "<td class=\"Table-data\">" . nl2br(sanitize_filter($value)) . "</td>" . PHP_EOL;
            $result_fragment .= "</tr>" . PHP_EOL;
        }
        $result_fragment .= "</tbody>" . PHP_EOL;
        $result_fragment .= "</table>" . PHP_EOL;
    }
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

    // Upon successful processing the values from the form fields are being emptied.
    if (isset($error_fragment)) {
        $number_value = autofill_form_field(NUMBER);
    } else {
        $number_value = null;
    }

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
                                <input type="number" id="$number_key" name="$number_key"
                                       value="$number_value" class="InputCombo-field">
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
                    <h2 class="Section-heading">Result in \$_POST</h2>
                    $guesses
                    $result_fragment
                    $script_name
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
