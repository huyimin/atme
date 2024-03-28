<?php
session_start();

if (!isset($_SESSION['conversations'])) {
    $_SESSION['conversations'] = [];
}

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['token'];

$db = new mysqli('localhost', 'root', 'tt1201', 'chatgpt');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

function chatGPT($prompt, $apiKey, $db, $history = []) {
    $ch = curl_init('https://api.openai.com/v1/chat/completions');

    $messages = [];
    foreach ($history as $exchange) {
        $messages[] = ['role' => 'user', 'content' => $exchange['ask']];
        $messages[] = ['role' => 'assistant', 'content' => $exchange['ans']];
    }
    $messages[] = ['role' => 'user', 'content' => $prompt];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['model' => 'gpt-3.5-turbo', 'messages' => $messages]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    return json_decode($result, true);
}

function detectAndWrapCode($response) {
    $response = htmlspecialchars($response);
    $response = preg_replace('/(```)(\s*?[\w]*\n)([\s\S]*?)(```)/', '<pre><code>$3</code></pre>', $response);
    
    // Converts newlines in the <code> blocks, outside of ``` blocks handled above
    $response = nl2br($response);

    return $response;
}

$apiKey = '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';
$responseMessage = '';
$promptToShow = ''; // Initialize outside the IF to ensure scope

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token']) && $_POST['token'] == $_SESSION['token']) {
    if (!empty($_POST['prompt'])) {
        $prompt = trim($_POST['prompt']);
        $response = chatGPT($prompt, $apiKey, $db, $_SESSION['conversations']);
        if (isset($response['choices'][0]['message']['content'])) {
            $ans = $response['choices'][0]['message']['content'];
            $_SESSION['conversations'][] = ['ask' => $prompt, 'ans' => $ans];
            $ans = $db->real_escape_string($ans);
            $ask = $db->real_escape_string($prompt);
            $query = "INSERT INTO ai (ask, ans) VALUES ('$ask', '$ans')";
            if (!$db->query($query)) {
                echo "Database insert error: " . $db->error;
            }
            $responseMessage = detectAndWrapCode($ans); // Apply detectAndWrapCode here
            // After a successful prompt submission, don't need to show the prompt again
            $promptToShow = '';
        } else {
            echo "API response error or unexpected format.";
            $promptToShow = $prompt; // Keep the prompt in the textarea if there was an error
        }
    } else {
        echo "Prompt is required.";
        $promptToShow = $_POST['prompt']; // Retain entered prompt if validation fails
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Chat with GPT</title>
        <link rel="stylesheet" href="default.css">
    </head>
    <body>
        <form action="" method="post" id="promptForm">
            <label for="prompt">Enter your prompt:</label><br>
            <textarea id="prompt" name="prompt" rows="6" cols="50"><?php echo nl2br(htmlspecialchars($promptToShow)); ?></textarea><br>
            <input type="hidden" name="token" value="<?php echo nl2br(htmlspecialchars($token)); ?>">
            <input type="submit" value="Submit">
        </form>

        <?php if (!empty($_SESSION['conversations'])): ?>
            <div>
                <h2>Conversations:</h2>
                <?php foreach ($_SESSION['conversations'] as $exchange): ?>
                    <p><strong>You:</strong> <?php echo nl2br(htmlspecialchars($exchange['ask'])); ?></p>
                    <!-- Processing each response before output -->
                    <div><strong>Gpt:</strong> <?php echo detectAndWrapCode($exchange['ans']); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <script src="highlight.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                document.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightElement(block);
                });
            });
        </script>
    </body>
    </html>