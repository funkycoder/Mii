<?php
require_once('includes/connection.inc.php');
// create database connection
$conn = dbConnect('write', 'pdo');
if (isset($_POST['insert'])) {
    // initialize flag
    $OK = false;
    // if a file has been uploaded then process it
    if (isset($_POST['upload_new'])){
        $imageOK = false;
        require_once '/inc/Upload.inc.php';
        $upload = new \Mii\Mii_Upload('images/');
        $upload->move();
        $names = $upload->getFileNames();
        //$name will be an empty array if the upload failed
        if ($names) {
            $sql = 'INSERT INTO images(filename,caption) VALUES(?,?)';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array($names[0], $_POST['caption']));
            $imageOK = $stmt->rowCount();
        }
        //get the image's primary key or find out what went wrong
        if ($imageOK) {
            $image_id = $conn->lastInsertId();
        } else {
            $imageError = implode(' ', $upload->getMessages());
        }
    } else if (isset($_POST['image_id']) && !empty($_POST['image_id'])) {
        //get the primary key for the previously uploaded image
        $image_id = $_POST['image_id'];
    }
    //dont insert blog details if the image failed to upload
    if (!isset($imageError)) {
        //if $image_id has been set, insert it as a foreign key
        if (isset($image_id)) {
            $sql = 'INSERT INTO blog(image_id,title,article,created) VALUES(:image_id,:title,:article,NOW())';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':image_id', $image_id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $_POST['title'], PDO::PARAM_STR);
            $stmt->bindParam(':article', $_POST['article'], PDO::PARAM_STR);
        } else {
            // create SQL
            $sql = 'INSERT INTO blog (title, article, created)
		  VALUES(:title, :article, NOW())';
            // prepare the statement
            $stmt = $conn->prepare($sql);
            // bind the parameters and execute the statement
            $stmt->bindParam(':title', $_POST['title'], PDO::PARAM_STR);
            $stmt->bindParam(':article', $_POST['article'], PDO::PARAM_STR);
        }

        // execute and get number of affected rows
        $stmt->execute();
        $OK = $stmt->rowCount();
    }
    //if the blog entry was inserted successfully then check for categories
    if ($OK && isset($_POST['category'])) {
        //get the article's primary key
        $article_id = $conn->lastInsertId();
        foreach ($_POST['category'] as $cat_id) {
            if (is_numeric($cat_id)) {
                $value[] = "($article_id," . (int) $cat_id . ')';
            }
        }
        if ($values) {
            $sql = 'INSERT INTO article2cat (article_id,cat_id) VALUES ' . implode(',', $value);
            //execute the query and get error message if it fails
            if (!$conn->query($sql)) {
                $catError = $conn->errorInfo();
                if (isset($catError[2])) {
                    $catError = $catError[2];
                }
            }
        }
    }

    //redirect if successful or display error
    if ($OK && !isset($catError)) {
        header('Location: http://localhost/chapter12/blog_list_pdo.php');
        exit;
    } else {
        $error = $conn->errorInfo()[2];

        if (isset($imageError)) {
            $error .= ' ' . $imageError;
        }
        if (isset($catError)) {
            $error .=' ' . $catError;
        }
    }
}
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="utf-8">
        <title>Insert Blog Entry</title>
        <link href="styles/admin.css" rel="stylesheet" type="text/css">
    </head>

    <body>
        <h1>Insert New Blog Entry</h1>
        <?php
        if (isset($error)) {
            echo "<p>Error: $error</p>";
        }
        ?>
        <form id="form1" method="post" action="" enctype="multipart/form-data">

            <p>
                <label for="title">Title:</label>
                <input name="title" type="text" class="widebox" id="title" value="<?php
                if (isset($error)) {
                    echo htmlentities($_POST['title'], ENT_COMPAT, 'utf-8');
                }
                ?>">
            </p>
            <p>
                <label for="article">Article:</label>
                <textarea name="article" cols="60" rows="8" class="widebox" id="article"><?php
                    if (isset($error)) {
                        echo htmlentities($_POST['article'], ENT_COMPAT, 'utf-8');
                    }
                    ?></textarea>
            </p>
            <p>
                <label for="category">Categories:</label>
                <select name="category[]" size="5" multiple id="category">
                    <?php
                    //get categories
                    $getCats = 'SELECT cat_id,category FROM categories ORDER BY category';
                    $categories = $conn->query($getCats);
                    while ($row = $categories->fetch()) {
                        ?>
                        <option value="<?php echo $row['cat_id']; ?>" <?php
                        if (isset($_POST['category']) && in_array($row['cat_id'], $_POST['category'])) {
                            echo ' selected';
                        }
                        ?>>
                                    <?php echo $row['category']; ?>
                        </option>
                    <?php } ?>
                </select>
            </p>
            <p>
                <label for="image_id">Uploaded image:</label>
                <select name="image_id" id="image_id">
                    <option value="">Select image</option>
                    <?php
                    //get the list of the images
                    $getImages = 'SELECT image_id,filename FROM images ORDER BY filename';
                    $images = $conn->query($getImages);
                    while ($row = $images->fetch()) {
                        ?>
                        <option value="<?php echo $row['image_id']; ?>"
                        <?php
                        if (isset($_POST['image_id']) && $row['image_id'] == $_POST['image_id']) {
                            echo ' selected';
                        }
                        ?>><?php echo $row['filename']; ?></option>
                            <?php } ?>
                </select>

            </p>
            <p id="allowUpload">
                <input type="checkbox" name="upload_new" id="upload_new" />
                <label for="upload_new">Upload new image</label>
            </p>
            <p class="optional">
                <label for="image">Select image:</label>
                <input type="file" name="image" id="image">
            </p>
            <p class="optional">
                <label for="caption">Caption</label>
                <input class="widebox" type="text" name="caption" id="caption">
            </p>
            <p>
                <input type="submit" name="insert" value="Insert New Entry" id="insert">
            </p>
        </form>
        <script src="js/toggle_fields.js"></script>
    </body>
</html>