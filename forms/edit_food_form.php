<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Food Item</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Link to your custom CSS -->
    <link rel="stylesheet" href="../css/form.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="card shadow p-4">
            <h2 class="text-center mb-4">Edit Food Item</h2>
            <form method="POST" action="php/edit_food.php?id=<?php echo $food_id; ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Food Name:</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($food_item['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description:</label>
                    <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($food_item['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Price (in Birr):</label>
                    <input type="number" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($food_item['price'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="image_url" class="form-label">Food Image URL:</label>
                    <input type="text" id="image_url" name="image_url" class="form-control" value="<?php echo htmlspecialchars($food_item['image_url'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary w-100">Update Food Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>