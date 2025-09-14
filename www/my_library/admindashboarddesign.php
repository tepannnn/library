<?php
session_start();
include("db_connect.php"); // ✅ your MySQL connection file

// borrower info from login session
$borrowerId = $_SESSION['borrower_id'];
$username   = $_SESSION['username'];

// ✅ Load available books (with optional search)
function LoadAvailableBooks($conn, $search = "") {
    $sql = "SELECT b.book_id, b.title, IFNULL(a.name,'Unknown') AS author, b.isbn, b.genre, b.quantity
            FROM books b
            LEFT JOIN authors a ON b.author_id = a.author_id
            WHERE b.quantity > 0";

    if (!empty($search)) {
        $sql .= " AND (b.title LIKE ? OR a.name LIKE ? OR b.isbn LIKE ? OR b.genre LIKE ?)";
    }

    $stmt = $conn->prepare($sql);

    if (!empty($search)) {
        $s = "%$search%";
        $stmt->bind_param("ssss", $s, $s, $s, $s);
    }

    $stmt->execute();
    return $stmt->get_result();
}

// ✅ Load my borrowed books
function LoadBorrowedBooks($conn, $borrowerId) {
    $sql = "SELECT bb.borrow_id, b.title AS book_title, IFNULL(a.name,'Unknown') AS author, 
                   b.isbn, b.genre, bb.quantity_borrowed, bb.borrow_date
            FROM borrowed_books bb
            JOIN books b ON bb.book_id = b.book_id
            LEFT JOIN authors a ON b.author_id = a.author_id
            WHERE bb.borrower_id=? AND bb.quantity_borrowed > 0";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $borrowerId);
    $stmt->execute();
    return $stmt->get_result();
}

// ✅ Borrow book
if (isset($_POST['borrow'])) {
    $bookId = $_POST['book_id'];
    $qty    = intval($_POST['qty']);

    // check available quantity
    $check = $conn->prepare("SELECT quantity FROM books WHERE book_id=?");
    $check->bind_param("i", $bookId);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    $available = $res['quantity'];

    if ($qty > 0 && $qty <= $available) {
        // insert borrowed_books
        $stmt = $conn->prepare("INSERT INTO borrowed_books(borrower_id, book_id, quantity_borrowed, borrow_date)
                                VALUES(?,?,?,NOW())");
        $stmt->bind_param("iii", $borrowerId, $bookId, $qty);
        $stmt->execute();

        // update stock
        $upd = $conn->prepare("UPDATE books SET quantity=quantity-? WHERE book_id=?");
        $upd->bind_param("ii", $qty, $bookId);
        $upd->execute();

        echo "<script>alert('Book borrowed successfully!');</script>";
    } else {
        echo "<script>alert('Invalid quantity. Available: $available');</script>";
    }
}

// ✅ Return book
if (isset($_POST['return'])) {
    $borrowId = $_POST['borrow_id'];
    $qty      = intval($_POST['qty']);

    // get book_id and current borrowed qty
    $stmt = $conn->prepare("SELECT book_id, quantity_borrowed FROM borrowed_books WHERE borrow_id=?");
    $stmt->bind_param("i", $borrowId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $bookId = $row['book_id'];
    $qtyBorrowed = $row['quantity_borrowed'];

    if ($qty > 0 && $qty <= $qtyBorrowed) {
        // update borrowed_books
        $upd = $conn->prepare("UPDATE borrowed_books SET quantity_borrowed=quantity_borrowed-? WHERE borrow_id=?");
        $upd->bind_param("ii", $qty, $borrowId);
        $upd->execute();

        // update stock
        $upd2 = $conn->prepare("UPDATE books SET quantity=quantity+? WHERE book_id=?");
        $upd2->bind_param("ii", $qty, $bookId);
        $upd2->execute();

        // delete if 0 left
        $del = $conn->prepare("DELETE FROM borrowed_books WHERE borrow_id=? AND quantity_borrowed<=0");
        $del->bind_param("i", $borrowId);
        $del->execute();

        echo "<script>alert('Return successful!');</script>";
    } else {
        echo "<script>alert('Invalid return quantity. Borrowed: $qtyBorrowed');</script>";
    }
}

// ✅ Logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// get book lists
$availableBooks = LoadAvailableBooks($conn, $_POST['search'] ?? "");
$borrowedBooks  = LoadBorrowedBooks($conn, $borrowerId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Borrower Dashboard</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>

    <!-- ✅ Search -->
    <form method="post">
        <input type="text" name="search" placeholder="Search books..." value="<?php echo $_POST['search'] ?? ''; ?>">
        <button type="submit">Search</button>
    </form>

    <!-- ✅ Available Books -->
    <h3>Available Books</h3>
    <table>
        <tr>
            <th>Title</th><th>Author</th><th>ISBN</th><th>Genre</th><th>Available</th><th>Action</th>
        </tr>
        <?php while($row = $availableBooks->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['title']); ?></td>
            <td><?= htmlspecialchars($row['author']); ?></td>
            <td><?= htmlspecialchars($row['isbn']); ?></td>
            <td><?= htmlspecialchars($row['genre']); ?></td>
            <td><?= $row['quantity']; ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="book_id" value="<?= $row['book_id']; ?>">
                    <input type="number" name="qty" value="1" min="1" max="<?= $row['quantity']; ?>">
                    <button type="submit" name="borrow">Borrow</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- ✅ Borrowed Books -->
    <h3>My Borrowed Books</h3>
    <table>
        <tr>
            <th>Title</th><th>Author</th><th>ISBN</th><th>Genre</th><th>Qty Borrowed</th><th>Borrow Date</th><th>Action</th>
        </tr>
        <?php while($row = $borrowedBooks->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['book_title']); ?></td>
            <td><?= htmlspecialchars($row['author']); ?></td>
            <td><?= htmlspecialchars($row['isbn']); ?></td>
            <td><?= htmlspecialchars($row['genre']); ?></td>
            <td><?= $row['quantity_borrowed']; ?></td>
            <td><?= $row['borrow_date']; ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="borrow_id" value="<?= $row['borrow_id']; ?>">
                    <input type="number" name="qty" value="1" min="1" max="<?= $row['quantity_borrowed']; ?>">
                    <button type="submit" name="return">Return</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <!-- ✅ Logout -->
    <form method="post">
        <button type="submit" name="logout">Logout</button>
    </form>
</body>
</html>
