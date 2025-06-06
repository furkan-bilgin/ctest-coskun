<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

include_once("processor.php");

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

define("RESULTS_FILE", "results/results.csv");
define("RESULTS_PER_USER_DIR", "results/per_user/");
define("CAPTCHA_ENABLED", true);

function render_single_form($passage_id, $passage_file, $current_form)
{
    $extracted_words = extract_words_from_file($passage_file);
    $total_words = count($extracted_words);

    if ($current_form >= $total_words) {
        return false;
    }

    echo "<div class='d-flex justify-content-between'>";
    echo "<strong>";
    echo ($current_form + 1) . " / " . $total_words;
    echo "</strong>";
    echo '<div class="text-bg-warning rounded ps-2 text-center shadow pe-2 mb-2" style="width: 3.5rem;" data-time-left=' . ($_SESSION['end_time'] - time()) . '></div>';
    echo "</div>";
    echo "<progress value='" . ($current_form + 1) . "' max='" . $total_words . "'></progress>";
    echo "<br>";

    $content = file_get_contents($passage_file);

    $modified_content = $content;

    foreach ($extracted_words as $idx => $word) {
        $pattern = '/\[' . preg_quote($word, '/') . '\]/';

        if ($idx < $current_form) {
            $replacement = '<strong>' . $word . '</strong><input class="passage-input" type="text" name="passage[' . $passage_id . '][' . $idx . ']" disabled data-input-index="' . $idx . '">';
        } else if ($idx == $current_form) {
            $replacement = '<strong>' . $word . '</strong>';
            $replacement .= '<input class="passage-input active-input" type="text" autocapitalize="off" autocorrect="off"';
            $replacement .= ' onchange="this.value = this.value.replaceAll(\' \', \'\')" onkeypress="return event.key !== \' \'"';
            $replacement .= ' name="passage[' . $passage_id . '][' . $idx . ']" required autofocus data-input-index="' . $idx . '">';
        } else {
            $replacement = '<strong>' . $word . '</strong><input class="passage-input" type="text" name="passage[' . $passage_id . '][' . $idx . ']" disabled data-input-index="' . $idx . '">';
        }

        $modified_content = preg_replace($pattern, $replacement, $modified_content, 1);
    }

    echo $modified_content;

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const passageFormData = JSON.parse(localStorage.getItem('passageFormData') || '{}');
            const inputs = document.querySelectorAll('.passage-input');
            
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (passageFormData[name]) {
                    input.value = passageFormData[name];
                }
            });
        });
    </script>";

    return true;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap-utilities.min.css" />
    <style>
        .passage-input {
            display: inline-block;
            padding: 0.5rem !important;
            width: 5rem !important;
            height: 2rem !important;
            color: #212529 !important;
            font-weight: normal !important;
            background-color: #ffffff !important;
        }

        .passage-input:disabled {
            background-color: #f8f9fa;
            color: #212529;
            border-color: #ced4da;
            opacity: 1;
        }

        .passage-input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            background-color: #ffffff !important;
        }

        .active-input {
            border: 2px solid #0d6efd !important;
            background-color: #ffffff !important;
        }
    </style>
    <?php
    $passages = [];
    foreach (scandir("passages") as $file) {
        if (substr($file, -4) == ".txt") {
            $passages[] = "passages/" . $file;
        }
    }
    $correctAnswers = file("correct_answers.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $correctAnswers = array_map('trim', $correctAnswers);

    $poll_questions = require "poll_questions.php";
    $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;

    if (isset($_COOKIE['done']) && $_COOKIE['done'] == 'true') {
        $current_page = 0;
    }
    ?>
</head>

<body>
    <main class="container">
        <h1>C-Test</h1>
        <?php
        if ($current_page === 0) {
        ?>
            <h4>Teşekkürler!</h4>
            <p>Testi tamamladınız. Sonuçlarınız kaydedildi.</p>
        <?php
        } else if ($current_page === 1) {
            session_destroy();
        ?>
            <script>
                // Clear local storage on the initial page
                localStorage.clear();
            </script>
            <style>
                .required label::after {
                    content: "*";
                    padding-left: 0.25rem;
                    color: red;
                }
            </style>
            <h4>Hoş Geldiniz!</h4>
            <form method="post" action="?page=2" class="mt-4">
                <input type="hidden" name="data" />
                <label for="participant_name">Adınız (opsiyonel)</label>
                <input type="text" name="participant_name" placeholder="Adınız (opsiyonel)" />
                <?php foreach ($poll_questions as $question_id => $question) { ?>
                    <div class="form-group required">
                        <label for="<?= $question_id ?>"><?= $question['question'] ?></label>
                        <select name="<?= $question_id ?>" id="<?= $question_id ?>" required>
                            <option value="">Lütfen seçiniz</option>
                            <?php foreach ($question['options'] as $option_id => $option) { ?>
                                <option value="<?= $option_id ?>"><?= $option ?></option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } ?>
                <button class="w-100">Başla</button>
            </form>
        <?php } else if ($current_page === 2) {
            $_SESSION['poll_filled_in'] = true;
        ?>
            <h4>Bilgilendirme</h4>
            <p>Test süresince 7 dakika süreniz olacaktır. 2 farklı metin doldurmanız beklenmektedir</p>
            <p>
                Aşağıda Türkçe iki kısa metinler okuyacaksınız. Her bir metin bazı kelimelerin
                tamamlanmadan bırakıldığı boşluklar içeriyor. Metni okurken boşlukları tamamlayınız.
                Kelime tamamlama için birden fazla seçenek mümkün olabilir bu nedenle doğru ya da yanlış
                cevap olmadığını aklınızda bulundurunuz. Katılımınız için teşekkürler.
            </p>
            <p>
                <strong>ÖNEMLİ NOT:</strong> Kelimeleri Türkçe karakterle uygun şekilde yazmaya çalışınız . Kelime size göre nasıl yazılıyorsa o şekilde yazınız
            </p>
            <form method="post" action="?page=3&form=0">
                <?php if (CAPTCHA_ENABLED) { ?>
                    <div class="cf-turnstile" data-sitekey="0x4AAAAAABb38N1cQxxRAHAQ"></div>
                <?php } ?>
                <input type="hidden" name="data" />
                <button class="w-100">Sonraki</button>
            </form>
        <?php } else if ($current_page === 3 || $current_page === 4) {
            $passage_id = $current_page - 3;
            $current_form = isset($_GET['form']) ? intval($_GET['form']) : 0;

            require_once("captcha.php");
            if (!isset($_SESSION['turnstile_verified']) && CAPTCHA_ENABLED) {
                if (!checkTurnstile()) {
                    die("Captcha doğrulanamadı!");
                    exit;
                }
                $_SESSION['turnstile_verified'] = true;
            }
            if (!isset($_SESSION['poll_filled_in'])) {
                header("Location: /ctest/");
                exit;
            }
        ?>
            <h4>Metin <?= $passage_id + 1 ?></h4>
            <hr>
            <?php
            if (!isset($_SESSION['end_time'])) {
                $_SESSION['end_time'] = time() + 7 * 60;
                error_log("Timer set to: " . $_SESSION['end_time'] . " (now: " . time() . ")");
            }

            $passage = file_get_contents($passages[$passage_id]);
            ?>
            <form method="post">
                <input type="hidden" name="data" />

                <div class="alert alert-info">
                    <strong>Bilgi:</strong>
                    <ul>
                        <li>Metni tamamen görebilirsiniz ve önceki cevaplarınızı görebilirsiniz</li>
                        <li>Tamamladığınız kelimelere geri dönüp değiştiremezsiniz</li>
                        <li>Testi sırayla tamamlamalısınız</li>
                        <li>Metnin bütününün bağlamı, doğru tamamlamaları belirlemenize yardımcı olur</li>
                    </ul>
                </div>

                <?php
                $passage_words = extract_words_from_file($passages[$passage_id]);
                $total_forms = count($passage_words);

                if ($current_form >= $total_forms) {
                    $next_page = $passage_id >= count($passages) - 1 ? 5 : ($current_page + 1);
                    header("Location: ?page=" . $next_page);
                    exit;
                }

                render_single_form($passage_id, $passages[$passage_id], $current_form);

                $is_last_form = ($current_form >= $total_forms - 1);
                $is_last_passage = ($passage_id >= count($passages) - 1);
                $button_text = $is_last_form && $is_last_passage ? "Gönder" : "İleri";
                ?>
                <button type="submit" class="w-100 mt-4"><?= $button_text ?></button>
            </form>
        <?php } else if ($current_page === 5) {
            $data = json_decode($_POST['data'], true);
            if ($data === null) {
                die("Geçersiz veri.");
            }
            if (!isset($_SESSION['turnstile_verified']) && CAPTCHA_ENABLED) {
                die("Captcha doğrulanamadı!");
            }

            $participant_name = isset($data['participant_name']) ? $data['participant_name'] : null;

            $results_file_exists = file_exists(RESULTS_FILE);
            $results_file = fopen(RESULTS_FILE, 'a');

            if (!$results_file_exists) {
                $user_id = 1;
            } else {
                $user_id = count(file(RESULTS_FILE));
            }

            if ($participant_name == null) {
                $participant_name = "User " . $user_id;
            }

            $csv_poll_keys = [];
            foreach (array_keys($data) as $key) {
                if (!str_starts_with($key, 'poll'))
                    continue;
                $csv_poll_keys[] = $poll_questions[$key]['question'];
            }

            $csv_keys = ['participant_id', 'participant_name'] + $csv_poll_keys;
            $csv_data = [$user_id, $participant_name];

            $passages_parsed = [];
            foreach ($passages as $passage_file) {
                $extracted_passage = extract_words_from_file($passage_file);
                $passages_parsed[] = $extracted_passage;
            }

            foreach ($data as $dataKey => $dataValue) {
                if (str_starts_with($dataKey, 'poll') && isset($poll_questions[$dataKey])) {
                    $question_id = $dataKey;
                    $question = $poll_questions[$question_id];
                    $option_id = $data[$question_id];
                    if (isset($question['options'][$option_id])) {
                        $csv_data[] = $question['options'][$option_id];
                    } else {
                        $csv_data[] = "";
                    }
                }
            }

            $csv_keys_per_user = ['participant_id', 'input_id', 'participant_answer', 'correct_answer'];
            $csv_data_per_user = [];

            $total_input_id = 0;
            foreach ($passages_parsed as $passage_id => $passage) {
                foreach ($passage as $input_id => $input) {
                    $test_key = 'passage[' . $passage_id . '][' . $input_id . ']';
                    $csv_keys[] = 'passage_' . ($passage_id + 1) . '_input_' . ($input_id + 1);
                    $participant_answer = "";

                    if (isset($data[$test_key]) && $data[$test_key] !== '') {
                        $participant_answer = $passages_parsed[$passage_id][$input_id] . $data[$test_key];
                    }
                    $csv_data[] = $participant_answer;
                    $csv_data_per_user[] = [$user_id, $total_input_id, $participant_answer, $correctAnswers[$total_input_id]];

                    $total_input_id++;
                }
            }

            $results_per_user_file = fopen(RESULTS_PER_USER_DIR . $user_id . '.csv', 'w');
            fputcsv($results_per_user_file, $csv_keys_per_user);
            foreach ($csv_data_per_user as $row) {
                fputcsv($results_per_user_file, $row);
            }
            fclose($results_per_user_file);

            if (!$results_file_exists)
                fputcsv($results_file, $csv_keys);
            fputcsv($results_file, $csv_data);
            fclose($results_file);

            session_destroy();
            setcookie("done", "true", time() + 60 * 60 * 24 * 30, "/");
        ?>
            <h4>Teşekkürler!</h4>
            <p>Testi tamamladınız. Sonuçlarınız kaydedildi.</p>
        <?php } else { ?>
            <h4>Geçersiz sayfa!</h4>
            <p><a href="/ctest/">Ana sayfaya dön</a></p>
        <?php } ?>
    </main>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
        (() => {
            const currentPage = "<?= $current_page ?>";
            if (localStorage.getItem('done') === 'true' && currentPage !== '3') {
                return;
            }

            const form = document.querySelector('form');
            let timeLeftInSeconds = 0;
            if (!form)
                return;

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                const data = new FormData(form);

                if (currentPage === "1" || currentPage === "2") {
                    let passageFormData = JSON.parse(localStorage.getItem('passageFormData') || '{}');
                    passageFormData = Object.assign(passageFormData, Object.fromEntries(data.entries()));
                    localStorage.setItem('passageFormData', JSON.stringify(passageFormData));

                    if (currentPage === "1") {
                        form.action = "?page=2";
                    } else {
                        form.action = "?page=3&form=0";
                    }

                    document.querySelector('input[name="data"]').value = JSON.stringify(passageFormData);
                    form.submit();
                    return;
                }

                const activeInput = document.querySelector('.active-input');
                if (!activeInput) {
                    const nextPage = currentPage === "3" ? 4 : 5;
                    form.action = '?page=' + nextPage + (nextPage < 5 ? '&form=0' : '');

                    let passageFormData = JSON.parse(localStorage.getItem('passageFormData') || '{}');
                    document.querySelector('input[name="data"]').value = JSON.stringify(passageFormData);
                    form.submit();
                    return;
                }

                const currentInputIndex = parseInt(activeInput.dataset.inputIndex);
                const allInputs = Array.from(document.querySelectorAll('.passage-input'));
                const sortedInputs = allInputs.sort((a, b) => {
                    return parseInt(a.dataset.inputIndex) - parseInt(b.dataset.inputIndex);
                });

                let nextInputIndex = -1;
                let foundNextEmpty = false;

                for (let i = 0; i < sortedInputs.length; i++) {
                    const inputIndex = parseInt(sortedInputs[i].dataset.inputIndex);
                    if (inputIndex > currentInputIndex && !sortedInputs[i].value) {
                        nextInputIndex = inputIndex;
                        foundNextEmpty = true;
                        break;
                    }
                }

                if (activeInput.value === '' && timeLeftInSeconds > 0) {
                    // alert('Formu boş bırakmayın.');
                    return;
                }

                let passageFormData = JSON.parse(localStorage.getItem('passageFormData') || '{}');
                passageFormData = Object.assign(passageFormData, Object.fromEntries(data.entries()));
                localStorage.setItem('passageFormData', JSON.stringify(passageFormData));

                if (timeLeftInSeconds > 0) {
                    if (foundNextEmpty) {
                        form.action = '?page=' + currentPage + '&form=' + nextInputIndex;
                    } else {
                        const nextPage = currentPage === "3" ? 4 : 5;
                        form.action = '?page=' + nextPage + (nextPage < 5 ? '&form=0' : '');
                    }
                } else {
                    form.action = "?page=5";
                }

                document.querySelector('input[name="data"]').value = JSON.stringify(passageFormData);
                form.submit();
            });

            const startDate = new Date();

            function updateTimeLeft() {
                const timeLeft = document.querySelector('div[data-time-left]');
                if (!timeLeft)
                    return;
                const timeLeftValue = parseInt(timeLeft.dataset.timeLeft) || 420;
                timeLeftInSeconds = timeLeftValue - ((new Date().getTime()) - (startDate.getTime())) / 1000;

                if (timeLeftInSeconds <= 0) {
                    clearInterval(interval);
                    form.dispatchEvent(new Event('submit'));
                    return;
                }

                const diffInMinutes = Math.floor(timeLeftInSeconds / 60);
                const diffInSeconds = Math.floor(timeLeftInSeconds % 60);
                timeLeft.innerHTML = `${diffInMinutes}:${diffInSeconds < 10 ? '0' : ''}${diffInSeconds}`;
            }
            const interval = setInterval(updateTimeLeft, 250);
            updateTimeLeft();
        })();

        window.addEventListener('beforeunload', () => {
            // Persist scroll position
            sessionStorage.setItem('scrollPosition', document.documentElement.scrollTop);
        });
        window.addEventListener('load', () => {
            // Restore scroll position
            const scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition) {
                document.documentElement.scrollTop = scrollPosition;
            }
        });
    </script>
</body>

</html>