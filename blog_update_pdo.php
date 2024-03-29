<?php
require_once('includes/connection.inc.php');
// initialize flags
$OK = false;
$done = false;
// create database connection
$conn = dbConnect('write', 'pdo');
// get details of selected record
if (isset($_GET['article_id']) && !$_POST) {
    // prepare SQL query
    $sql = 'SELECT article_id,image_id, title, article FROM blog
		  WHERE article_id = ?';
    $stmt = $conn->prepare($sql);
    // bind the results
    $stmt->bindColumn(1, $article_id);
    $stmt->bindColumn(2, $image_id);
    $stmt->bindColumn(3, $title);
    $stmt->bindColumn(4, $article);
    // execute query by passing array of variables
    $OK = $stmt->execute(array($_GET['article_id']));
    //error?
    if (!$OK) {
        $error = $conn->errorInfo()[2];
    }
    $stmt->fetch();
    //free the database for the second query
    $stmt->closeCursor();
    //get categories associated with the article
    $sql = 'SELECT cat_id from article2cat WHERE article_id=?';
    $stmt = $conn->prepare($sql);
    $stmt->bindColumn(1, $cat_id);
    $OK = $stmt->execute(array($_GET['article_id']));
    //now loop through the result and store them in an array
    while ($stmt->fetch()) {
        $selectedCategories[] = $cat_id;
    }
}
// if form has been submitted, update record
if (isset($_POST['update'])) {
    // prepare update query
    $article_id = $_POST['article_id'];
    $title = $_POST['title'];
    $article = $_POST['article'];
    if (!empty($_POST['image_id'])) {
        $image_id = $_POST['image_id'];
    } else {
        $image_id = NULL;
    }
    $sql = 'UPDATE blog SET image_id=?, title = ?, article = ?
		  WHERE article_id = ?';
    $stmt = $conn->prepare($sql);
    // execute query by passing array of variables
    $done = $stmt->execute(array($image_id, $title, $article, $article_id));
    //   $rowCount = $stmt->rowCount();
    //delete existing values in the cross-reference table
    $sql = 'DELETE FROM article2cat WHERE article_id=?';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array($_POST['article_id']));
    //insert the new values in the article2cat 
    if (isset($_POST['category']) && is_numeric($_POST['article_id'])) {
        $article_id = (int) $_POST['article_id'];
        foreach ($_POST['category'] as $cat_id) {
            $values[] = "($article_id," . (int) $cat_id . ')';
        }
        if ($values) {
            $sql = 'INSERT INTO article2cat (article_id,cat_id) VALUES ' . implode(',', $values);
            if (!$conn->query($sql)) {
                $catError = $conn->errorInfo()[2];
            }
        }
    }
}
// redirect if $_GET['article_id'] not defined
if ($done || !isset($_GET['article_id'])) {
    header('Location: http://localhost/chapter12/blog_list_pdo.php');
    exit;
}
// display error message if query fails
if (isset($stmt) && !$OK && !$done) {
    $error .=' ' . $stmt->errorInfo()[2];
}
// display error message if insert categories failed
if (isset($catError)) {
    $error .=' ' . $catError;
}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8">
        <title>Update Blog Entry</title>
        <link href="styles/admin.css" rel="stylesheet" type="text/css">
    </head>

    <body>
        <h1>Update Blog Entry</h1>
        <p><a href="blog_list_pdo.php">List all entries </a></p>
        <?php
        if (isset($error)) {
            if (is_array($error)) {
                $error = implode(' ', $error);
            }
            echo "<p class='warning'>Error: $error</p>";
        }
        if ($article_id == 0) {
            ?>
            <p class="warning">Invalid request: record does not exist.</p>
        <?php } else { ?>
            <form id="form1" method="post" action="">
                <p>
                    <label for="title">Title:</label>
                    <input name="title" type="text" class="widebox" id="title" value="<?php echo htmlentities($title, ENT_COMPAT, 'utf-8'); ?>">
                </p>
                <p>
                    <label for="article">Article:</label>
                    <textarea name="article" cols="60" rows="8" class="widebox" id="article"><?php echo htmlentities($article, ENT_COMPAT, 'utf-8'); ?></textarea>
                </p>
                <p>
                    <label for="category">Categories:</label>
                    <select name="category[]" size="5" multiple id="category">
                        <?php
                        // get categories
                        $getCats = 'SELECT cat_id, category FROM categories ORDER BY category';
                        foreach ($conn->query($getCats) as $row) {
                            ?>
                            <option value="<?php echo $row['cat_id']; ?>" <?php
                    if ((!empty($selectedCategories))&&in_array($row['cat_id'], $selectedCategories)) {
                        echo ' selected';
                    }
                            ?>><?php echo $row['category']; ?></option>
                                <?php } ?>
                    </select>
                </p>

                <p>
                    <label for="image_id">Uploaded images</label>
                    <select name="image_id" id="image_id">
                        <option value="">Select an image</option>
                        <?php
                        //get details of the images
                        $getImages = 'SELECT image_id,filename FROM images ORDER BY filename';
                        foreach ($conn->query($getImages) as $row) {
                            ?>                                                                 
                            <option value="<?php echo $row['image_id']; ?>"
                            <?php
                            if ($row['image_id'] == $image_id) {
                                echo ' selected';
                            }
                            ?>> <?php echo $row['filename']; ?> </option>                
                                <?php } $conn->query($getImages)->closeCursor(); ?>
                    </select>
                </p>
                <p>
                    <input type="submit" name="update" value="Update Entry" id="update">
                    <input name="article_id" type="hidden" value="<?php echo $article_id; ?>">

                </p>
            </form>
        <?php } ?>
    </body>
</html>