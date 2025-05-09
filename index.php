<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();

include_once("processor.php");

// Polyfill for str_starts_with for PHP versions below 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

define("RESULTS_FILE", "results/results.csv");
define("RESULTS_PER_USER_DIR", "results/per_user/");
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
        }
    </style>
    <?php
    // Read all files in "passages" directory, get all that end with ".txt"
    $passages = [];
    foreach (scandir("passages") as $file) {
        if (substr($file, -4) == ".txt") {
            $passages[] = "passages/" . $file;
        }
    }


    $poll_questions = [
        'poll_gender' => [
            'question' => 'Cinsiyet',
            'options' => [
                'male' => 'Erkek',
                'female' => 'Kadın',
                'other' => 'Diğer'
            ]
        ],
        'poll_education' => [
            'question' => 'Eğitim seviyeniz',
            'options' => [
                'university' => 'Üniversite',
                'high_school' => 'Lise',
                'middle_school' => 'Ortaokul',
                'primary_school' => 'İlkokul',
            ]
        ],
        'poll_country' => [
            'question' => 'Ülke',
            'options' => [
                'turkey' => 'Türkiye',
                'germany' => 'Almanya',
                'france' => 'Fransa',
                'italy' => 'İtalya',
                'spain' => 'İspanya',
                'uk' => 'Birleşik Krallık',
                'austria' => 'Avusturya',
                'belgium' => 'Belçika',
                'denmark' => 'Danimarka',
                'finland' => 'Finlandiya',
                'greece' => 'Yunanistan',
            ]
        ],
        'poll_tr_frequency' => [
            'question' => 'Ne sıklıkla Türkçe konuşuyorsunuz?',
            'options' => [
                'never' => 'Asla',
                'rarely' => 'Nadiren',
                'sometimes' => 'Bazen',
                'often' => 'Sık Sık',
                'always' => 'Her Zaman',
            ]
        ],
        'poll_language_at_home' => [
            'question' => 'Evde en çok hangi dili konuşuyorsunuz?',
            'options' => [
                'german' => 'Almanca',
                'turkish' => 'Türkçe',
                'other' => 'Diğer',
            ]
        ],
        'poll_language_with_family' => [
            'question' => 'Ailenizle en çok hangi dili konuşuyorsunuz?',
            'options' => [
                'only_german' => 'Sadece Almanca',
                'mostly_german' => 'Çoğu zaman Almanca',
                'equal' => 'Eşit',
                'mostly_turkish' => 'Çoğu zaman Türkçe',
                'only_turkish' => 'Sadece Türkçe',
            ]
        ],
        'poll_importance_of_turkish' => [
            'question' => 'Türkçenizi unutmamak, dilinizi korumak sizin için önemli mi?',
            'options' => [
                'not_important' => 'Önemsiz',
                'less_important' => 'Az önemli',
                'somewhat_important' => 'Biraz önemli',
                'important' => 'Önemli',
                'very_important' => 'Çok önemli',
            ]
        ],
        'poll_friend_nationality' => [
            'question' => 'Arkadaşlarınız genel olarak Türk mü yoksa Avusturya/Almanyalı mı?',
            'options' => [
                'only_foreign' => 'Sadece Avusturyalı/Almanyalı',
                'mostly_foreign' => 'Çoğunlukla Avusturyalı/Almanyalı',
                'equal' => 'Eşit',
                'mostly_native' => 'Çoğunlukla Türk',
                'only_native' => 'Sadece Türk',
            ]
        ],
        'poll_turkish_media' => [
            'question' => 'Türkçe şarkı/podcast/film/radyo dinliyor musunuz?',
            'options' => [
                'never' => 'Asla',
                'rarely' => 'Nadiren',
                'sometimes' => 'Bazen',
                'often' => 'Sık Sık',
                'always' => 'Her Zaman',
            ]
        ],
        'poll_turkish_writing' => [
            'question' => 'Ne sıklıkla Türkçe yazarsınız?',
            'options' => [
                'never' => 'Asla',
                'rarely' => 'Nadiren',
                'sometimes' => 'Bazen',
                'often' => 'Sık Sık',
                'always' => 'Her Zaman',
            ]
        ],
        'poll_turkish_skills' => [
            'question' => 'Türkçe dil yeterliliğinizi nasıl buluyorsunuz?',
            'options' => [
                'quite_bad' => 'Çok kötü',
                'bad' => 'Kötü',
                'enough' => 'Yeterli',
                'good' => 'İyi',
                'quite_good' => 'Çok iyi',
            ]
        ],
    ];

    ?>
</head>

<body>
    <main class="container">
        <h1>C-Test</h1>
        <?php
        $current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($current_page === 1) { ?>
            <h4>Hoş Geldiniz!</h4>
            <form method="post" action="?page=2" class="mt-4">
                <input type="hidden" name="data" />
                <label for="participant_name">Adınız (opsiyonel)</label>
                <input type="text" name="participant_name" placeholder="Adınız (opsiyonel)" />
                <?php foreach ($poll_questions as $question_id => $question) { ?>
                    <label for="<?= $question_id ?>"><?= $question['question'] ?></label>
                    <select name="<?= $question_id ?>" id="<?= $question_id ?>" required>
                        <option value="">Lütfen seçiniz</option>
                        <?php foreach ($question['options'] as $option_id => $option) { ?>
                            <option value="<?= $option_id ?>"><?= $option ?></option>
                        <?php } ?>
                    </select>
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
                <strong>ÖNEMLİ NOT:<strong> Kelimeleri Türkçe karakterle uygun şekilde yazmaya çalışınız . Kelime size göre nasıl yazılıyorsa o şekilde yazınız
            </p>
            <form method="post" action="?page=3&test=0">
                <input type="hidden" name="data" />
                <div class="cf-turnstile" data-sitekey="0x4AAAAAABb38N1cQxxRAHAQ"></div>
                <button class="w-100">Sonraki</button>
            </form>
            <?php } else if ($current_page === 3) {
            $passage_id = intval($_GET['test']);
            require_once("captcha.php");
            if (!isset($_SESSION['turnstile_verified'])) {
                // Check if the captcha is verified
                if (!checkTurnstile()) {
                    die("Captcha doğrulanamadı!");
                    exit;
                }
                $_SESSION['turnstile_verified'] = true;
            }

            // If we run out of passages, it means the test is over
            if (!isset($passages[$passage_id])) {
                // Convert data to json
                $data = json_decode($_POST['data'], true);
                if ($data === null) {
                    die("Geçersiz veri.");
                    exit;
                }
                if (false && !isset($_SESSION['end_time'])) {
                    die("Test süresi doldu.");
                    exit;
                }
                $participant_name = isset($data['participant_name']) ? $data['participant_name'] : null;

                // Add data to results file
                $results_file_exists = file_exists(RESULTS_FILE);
                $results_file = fopen(RESULTS_FILE, 'a');

                $csv_poll_keys = [];
                foreach (array_keys($data) as $key) {
                    if (!str_starts_with($key, 'poll'))
                        continue;
                    $csv_poll_keys[] = $poll_questions[$key]['question'];
                }

                $csv_keys = ['participant_id', 'participant_name'] + $csv_poll_keys;

                // Get current line number
                if (!$results_file_exists) {
                    $user_id = 1;
                } else {
                    $user_id = count(file(RESULTS_FILE));
                }

                if ($participant_name == null) {
                    $participant_name = "User " . $user_id;
                }

                $csv_data = [$user_id, $participant_name];

                $passagesParsed = [];
                foreach ($passages as $passage_file) {
                    $extracted_passage = extract_words_from_file($passage_file);
                    $passagesParsed[] = $extracted_passage;
                }

                // Insert poll data
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

                $csv_keys_per_user = ['participant_id', 'input_id', 'participant_answer'];
                $csv_data_per_user = [];

                // Insert passage data
                $total_input_id = 0;
                foreach ($passagesParsed as $passage_id => $passage) {
                    foreach ($passage as $input_id => $input) {
                        $test_key = 'passage[' . $passage_id . '][' . $input_id . ']';
                        $csv_keys[] = 'passage_' . ($passage_id + 1) . '_input_' . ($input_id + 1);
                        $participant_answer = "";

                        if (isset($data[$test_key]) && $data[$test_key] !== '') {
                            $participant_answer = $passagesParsed[$passage_id][$input_id] . $data[$test_key];
                        }
                        $csv_data[] = $participant_answer;
                        $csv_data_per_user[] = [$user_id, $total_input_id, $participant_answer];

                        $total_input_id++;
                    }
                }

                $results_per_user_file = fopen(RESULTS_PER_USER_DIR . $user_id . '.csv', 'w');
                fputcsv($results_per_user_file, $csv_keys_per_user);
                foreach ($csv_data_per_user as $row) {
                    fputcsv($results_per_user_file, $row);
                }
                fclose($results_per_user_file);

                // Add data into results
                if (!$results_file_exists)
                    fputcsv($results_file, $csv_keys);
                fputcsv($results_file, $csv_data);
                fclose($results_file);
                session_destroy();
            ?>
                <h4>Teşekkürler!</h4>
                <hr>
                <p>Cevaplarınız kaydedildi. Bize zaman ayırdığınız için teşekkür ederiz.</p>
                <script>
                    localStorage.setItem('done', 'true');
                </script>
            <?php
            } else {
                if (!isset($_SESSION['poll_filled_in'])) {
                    // Redirect to the first page
                    header("Location: /ctest/");
                    exit;
                }
            ?>
                <h4>Metin <?= $passage_id + 1 ?></h4>
                <hr>
                <?php
                if (!isset($_SESSION['end_time'])) {
                    $_SESSION['end_time'] = time() + 7 * 60;
                }
                $passage = file_get_contents($passages[$passage_id]);
                ?>
                <div class="mb-4" data-time-left="<?= $_SESSION['end_time'] - time() ?>"></div>
                <form method="post">
                    <input type="hidden" name="data" />
                    <?php render_form_from_file($passage_id, $passages[$passage_id]); ?>
                    <?php if ($passage_id >= count($passages)) { ?>
                        <button type="submit" class="w-100 mt-4">Gönder</button>
                    <?php } else { ?>
                        <button type="submit" class="w-100 mt-4">İleri</button>
                    <?php } ?>
                </form>
        <?php }
        } ?>
    </main>
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
                // Alert if no form data is submitted
                let isAllEmpty = true;
                for (const [key, value] of data.entries()) {
                    if (!key.startsWith('passage'))
                        continue;
                    if (value !== '') {
                        isAllEmpty = false;
                        break;
                    }
                }
                <?php if (isset($passage_id)) { ?>
                    if (timeLeftInSeconds > 0) {
                        if (isAllEmpty) {
                            alert('Formu boş bırakmayın.');
                            return;
                        }
                        form.action = '?page=' + currentPage + '&test=<?= $passage_id + 1 ?>';
                    }
                <?php }  ?>
                // Save form data to local storage
                let passageFormData = JSON.parse(localStorage.getItem('passageFormData') || '{}');
                passageFormData = Object.assign(passageFormData, Object.fromEntries(data.entries()));
                localStorage.setItem('passageFormData', JSON.stringify(passageFormData));
                // Submit the form
                // Change form data to local storage
                document.querySelector('input[type="hidden"]').value = JSON.stringify(passageFormData);
                form.submit();
            });
            const startDate = new Date();

            function updateTimeLeft() {
                const timeLeft = document.querySelector('div[data-time-left]');
                if (!timeLeft)
                    return;
                const timeLeftValue = parseInt(timeLeft.dataset.timeLeft);
                timeLeftInSeconds = timeLeftValue - ((new Date().getTime()) - (startDate.getTime())) / 1000;
                if (timeLeftInSeconds <= 0) {
                    clearInterval(interval);
                    form.action = '?page=' + currentPage + '&test=-1';
                    // Emit submit event
                    form.dispatchEvent(new Event('submit'));
                    return;
                }
                const diffInMinutes = Math.floor(timeLeftInSeconds / 60);
                const diffInSeconds = Math.floor(timeLeftInSeconds % 60);
                timeLeft.innerHTML = `<strong>Kalan Süre:</strong> ${diffInMinutes}:${diffInSeconds < 10 ? '0' : ''}${diffInSeconds}`;
            }
            const interval = setInterval(updateTimeLeft, 250);
            updateTimeLeft();
        })();
    </script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</body>

</html>