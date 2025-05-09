<?php

/**
 * Reads a text file, extracts all substrings matching the pattern '[word]',
 * and returns a list of the 'word' parts.
 *
 * @param string $filepath The path to the text file.
 * @return array A list of strings found within square brackets.
 */
function extract_words_from_file(string $filepath): array
{
    if (!file_exists($filepath) || !is_readable($filepath)) {
        // Optionally, log an error or throw an exception
        return [];
    }

    $content = file_get_contents($filepath);
    if ($content === false) {
        // Optionally, log an error or throw an exception
        return [];
    }

    $matches = [];
    // Regex: \[ matches a literal '['
    // ([^\]]+) captures one or more characters that are NOT a literal ']' (this is group 1)
    // \] matches a literal ']'
    if (preg_match_all('/\[([^\]]+)\]/', $content, $matches)) {
        // $matches[0] would be the full match (e.g., "[word]")
        // $matches[1] contains the content of the first capturing group (e.g., "word")
        return $matches[1];
    }

    return [];
}

/**
 * Reads a text file and renders it as an HTML form.
 *
 * @param string $filepath The path to the text file.
 * @return void Outputs HTML directly.
 */
function render_form_from_file(int $passage_id, string $filepath): void
{
    if (!file_exists($filepath) || !is_readable($filepath)) {
        echo "<p>Error: File '" . htmlspecialchars($filepath) . "' not found or not readable.</p>";
        return;
    }

    $content = file_get_contents($filepath);
    if ($content === false) {
        echo "<p>Error: Could not read content from file '" . htmlspecialchars($filepath) . "'.</p>";
        return;
    }

    $words = extract_words_from_file($filepath);
    $input_counter = 0;

    $transformed_content = $content;
    foreach ($words as $word) {
        $placeholder_text = htmlspecialchars($word);

        $base_name = preg_replace('/[^a-z0-9_]+/', '_', strtolower($word));
        if (empty($base_name)) {
            $base_name = 'input';
        }
        $input_name = 'passage[' . $passage_id . '][' . $input_counter . ']';
        $input_counter++;

        $transformed_content = str_replace(
            '[' . $word . ']',
            '<div class="d-inline-flex justify-content-center align-items-center mb-2">' .
                $placeholder_text . '<input class="mb-0 passage-input" class="passage-input" type="text" name="' . htmlspecialchars($input_name) . '" />'
                . '</div>',
            $transformed_content
        );
    }
?>
    <?= $transformed_content ?>
<?php } ?>