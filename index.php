<?php
$host = getenv('MYSQL_HOST') ?: 'mysql';
$database = getenv('MYSQL_DATABASE') ?: 'form_app';
$username = getenv('MYSQL_USERNAME') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: 'root';

$connection = new mysqli($host, $username, $password, $database);
$connection->set_charset('utf8mb4');

$message = '';
$messageType = '';
$formText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formText = trim($_POST['form_text'] ?? '');

    if ($formText === '') {
        $message = 'Please enter some text.';
        $messageType = 'error';
    } elseif (mb_strlen($formText) > 1000) {
        $message = 'Please keep your text within 1000 characters.';
        $messageType = 'error';
    } else {
        $statement = $connection->prepare('INSERT INTO submissions (content) VALUES (?)');
        $statement->bind_param('s', $formText);

        if ($statement->execute()) {
            $message = 'Your submission was saved successfully.';
            $messageType = 'success';
            $formText = '';
        } else {
            $message = 'The submission could not be saved. Please try again.';
            $messageType = 'error';
        }

        $statement->close();
    }
}

$submissions = [];
$result = $connection->query('SELECT id, content, created_at FROM submissions ORDER BY id DESC');

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
}

$connection->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Form</title>
    <style>
        :root {
            --ink: #183044;
            --muted: #687b89;
            --line: #d9e3e8;
            --paper: #ffffff;
            --accent: #e8673d;
            --accent-dark: #c94d27;
            --background: #edf4f1;
            --success: #18794e;
            --error: #b83333;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background: linear-gradient(135deg, #edf4f1 0%, #f9efe7 100%);
            font-family: Georgia, 'Times New Roman', serif;
        }

        main {
            width: min(760px, calc(100% - 32px));
            margin: 0 auto;
            padding: 72px 0;
        }

        .intro { margin-bottom: 28px; }
        .eyebrow {
            margin: 0 0 10px;
            color: var(--accent-dark);
            font: 700 0.78rem/1.2 Arial, sans-serif;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        h1 { margin: 0; font-size: clamp(2.2rem, 6vw, 4.3rem); line-height: 0.98; }
        .intro p { max-width: 520px; margin: 18px 0 0; color: var(--muted); font: 1rem/1.6 Arial, sans-serif; }

        .panel {
            padding: 28px;
            border: 1px solid rgba(24, 48, 68, 0.12);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.84);
            box-shadow: 0 18px 50px rgba(24, 48, 68, 0.08);
        }

        label { display: block; margin-bottom: 10px; font: 700 0.92rem/1.3 Arial, sans-serif; }
        textarea {
            display: block;
            width: 100%;
            min-height: 130px;
            resize: vertical;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 5px;
            color: var(--ink);
            background: #fbfdfc;
            font: 1rem/1.5 Arial, sans-serif;
        }
        textarea:focus { outline: 3px solid rgba(232, 103, 61, 0.2); border-color: var(--accent); }
        button {
            margin-top: 16px;
            padding: 12px 20px;
            border: 0;
            border-radius: 5px;
            color: white;
            background: var(--accent);
            cursor: pointer;
            font: 700 0.95rem Arial, sans-serif;
        }
        button:hover { background: var(--accent-dark); }
        .notice { margin: 0 0 18px; font: 0.94rem Arial, sans-serif; }
        .success { color: var(--success); }
        .error { color: var(--error); }
        .entries { margin-top: 34px; }
        .entries h2 { margin: 0 0 16px; font-size: 1.5rem; }
        .entry { padding: 16px 0; border-top: 1px solid var(--line); }
        .entry p { margin: 0 0 8px; white-space: pre-wrap; font: 1rem/1.55 Arial, sans-serif; }
        time { color: var(--muted); font: 0.78rem Arial, sans-serif; }
        .empty { color: var(--muted); font: 0.95rem Arial, sans-serif; }

        @media (max-width: 520px) {
            main { padding: 42px 0; }
            .panel { padding: 20px; }
        }
    </style>
</head>
<body>
    <main>
        <header class="intro">
            <p class="eyebrow">MySQL + Raw PHP</p>
            <h1>Share your thoughts</h1>
            <p>Write something in the form and submit it. Your submission will appear below.</p>
        </header>

        <section class="panel">
            <?php if ($message !== ''): ?>
                <p class="notice <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post" action="">
                <label for="form_text">Your message</label>
                <textarea id="form_text" name="form_text" maxlength="1000" placeholder="Write here..." required><?= htmlspecialchars($formText, ENT_QUOTES, 'UTF-8') ?></textarea>
                <button type="submit">Submit</button>
            </form>

            <div class="entries">
                <h2>Submissions</h2>
                <?php if (count($submissions) === 0): ?>
                    <p class="empty">No submissions yet.</p>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <article class="entry">
                            <p><?= htmlspecialchars($submission['content'], ENT_QUOTES, 'UTF-8') ?></p>
                            <time datetime="<?= htmlspecialchars($submission['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($submission['created_at'], ENT_QUOTES, 'UTF-8') ?>
                            </time>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>

