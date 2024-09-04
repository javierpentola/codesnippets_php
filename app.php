<?php

function create_connection(){
    try {
        $conn = new PDO("mysql:host=localhost;dbname=wftutorials", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        return null;
    }
}

function persist_snippet($title, $language, $url, $content, $id = null){
    $conn = create_connection();
    if (!$conn) return null;
    
    $sql = $id 
        ? "UPDATE code_snippets SET title = ?, language = ?, content = ?, url = ? WHERE id = ?"
        : "INSERT INTO code_snippets (title, language, content, url) VALUES (?, ?, ?, ?)";

    $params = $id 
        ? [$title, $language, $content, $url, $id] 
        : [$title, $language, $content, $url];

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    return $id ? $id : $conn->lastInsertId();
}

function fetch_all_snippets(){
    try {
        $conn = create_connection();
        return $conn ? $conn->query("SELECT * FROM code_snippets")->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) {
        return [];
    }
}

function fetch_snippet_by_id($id){
    try {
        $conn = create_connection();
        $stmt = $conn->prepare("SELECT * FROM code_snippets WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function delete_snippet_by_id($id){
    $conn = create_connection();
    if ($conn && $id) {
        $stmt = $conn->prepare("DELETE FROM code_snippets WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    }
    return false;
}

$id = $title = $language = $url = $content = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $snippet = fetch_snippet_by_id($id);
    if ($snippet) {
        $title = $snippet['title'];
        $language = $snippet['language'];
        $url = $snippet['url'];
        $content = $snippet['content'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save-code'])) {
        $title = $_POST['title'] ?? null;
        $language = $_POST['language'] ?? null;
        $content = $_POST['content'] ?? null;
        $url = $_POST['url'] ?? null;

        if ($title && $language && $content) {
            $id = persist_snippet($title, $language, $url, $content, $id);
        }
    } elseif (isset($_POST['delete-code']) && $id) {
        if (delete_snippet_by_id($id)) {
            header("Location: app.php");
            exit();
        }
    } elseif (isset($_POST['delete-confirm'])) {
        echo '<form method="post">
                Are you sure you want to delete this?: 
                <input type="submit" name="delete-code" value="Yes" />
                <button onclick="javascript:history.back();">No</button>
              </form>';
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Code Snippets</title>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.6/styles/atom-one-dark.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.6/highlight.min.js"></script>
    <script>hljs.initHighlightingOnLoad();</script>
</head>
<body>
<style>
.container{
    width: 98%;
    background: #ddd;
    float: left;
    padding: 20px;
}
.form-container {
    width: 40%;
    display: inline-block;
}

.form-content{
    background: #f6f6f6;
    border-radius: 3px;
    border: 1px solid #ccc;
    padding: 20px;
}

.snippet-listing {
    background: #f6f6f6;
    border-radius: 3px;
    border: 1px solid #ccc;
    padding: 20px;
    min-height: 300px;
    max-height: 500px;
}

.snippet-listing-content {
    max-height: 200px; overflow: auto;
}

.view-container {
    width: 50%;
    float: right;
    background: #f6f6f6;
    border-radius: 3px;
    border: 1px solid #ccc;
    padding: 20px;
    min-height: 500px;
    margin-right: 3px;
}

.code-block code {
    border-radius: 3px;
    border: 4px solid #ccc;
}

</style>

<div class="container">
    <!-- Form -->
    <div class="form-container">
        <div class="form-content">
            <h3>Code Snippets</h3>
            <a href="app.php">Add New Snippet</a>
            <br><br>
            <form method="post">
                Title: <input type="text" name="title" value="<?= $title ?>" placeholder="Enter snippet title"/><br>
                Language:
                <select name="language">
                    <option>--Select a language--</option>
                    <?php foreach (['php', 'javascript', 'java', 'sql', 'bash', 'python'] as $lang): ?>
                        <option value="<?= $lang ?>" <?= $language == $lang ? 'selected' : '' ?>><?= strtoupper($lang) ?></option>
                    <?php endforeach; ?>
                </select><br>
                Web Link: <input type="text" name="url" value="<?= $url ?>" placeholder="What is the link"/><br>
                Code: <textarea name="content" cols="50" rows="7"><?= $content ?></textarea><br>
                <?php if ($id): ?>
                    <hr>
                    <button type="submit" name="delete-confirm">Delete Snippet</button>
                    <hr>
                <?php endif; ?>
                <button type="submit" name="save-code">Save Code Snippet</button>
            </form>
        </div>

        <div class="snippet-listing">
            <h3>All Snippets</h3>
            <div class="snippet-listing-content">
                <ul>
                    <?php foreach (fetch_all_snippets() as $snippet): ?>
                        <li><a href="app.php?id=<?= $snippet['id'] ?>"><?= $snippet['title'] ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Snippet Viewer -->
    <div class="view-container">
        <h3>View Code Snippet</h3>
        <?php if ($title): ?>
            <p><?= $title ?></p>
        <?php endif; ?>
        <?php if ($url): ?>
            <p><a href="<?= $url ?>" target="_blank"><?= $url ?></a></p>
        <?php endif; ?>
        <div class="code-block">
            <pre><code class="language-<?= $language ?>"><?= htmlspecialchars($content) ?></code></pre>
        </div>
    </div>
</div>

</body>
</html>
