<?php
include "db.php"; // connection file

// ✅ Load all books with author name
function loadBooks($conn) {
    $sql = "SELECT b.book_id, b.title, a.name AS author, b.isbn, b.genre, b.quantity
            FROM books b
            LEFT JOIN authors a ON b.author_id = a.author_id";
    $result = $conn->query($sql);

    $books = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    return $books;
}

// 🔹 Get or create author_id
function getAuthorId($conn, $authorName) {
    $stmt = $conn->prepare("SELECT author_id FROM authors WHERE name=?");
    $stmt->bind_param("s", $authorName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row["author_id"];
    } else {
        $stmt = $conn->prepare("INSERT INTO authors (name) VALUES (?)");
        $stmt->bind_param("s", $authorName);
        $stmt->execute();
        return $conn->insert_id;
    }
}

// ✅ Add new book
function addBook($conn, $title, $author, $isbn, $genre, $quantity) {
    $authorId = getAuthorId($conn, $author);

    $stmt = $conn->prepare("INSERT INTO books (title, author_id, isbn, genre, quantity) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sissi", $title, $authorId, $isbn, $genre, $quantity);

    return $stmt->execute();
}

// ✅ Update existing book
function updateBook($conn, $id, $title, $author, $isbn, $genre, $quantity) {
    $authorId = getAuthorId($conn, $author);

    $stmt = $conn->prepare("UPDATE books SET title=?, author_id=?, isbn=?, genre=?, quantity=? WHERE book_id=?");
    $stmt->bind_param("sissii", $title, $authorId, $isbn, $genre, $quantity, $id);

    return $stmt->execute();
}

// ✅ Delete book
function deleteBook($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM books WHERE book_id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// ✅ Search books
function searchBooks($conn, $search) {
    $like = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT b.book_id, b.title, a.name AS author, b.isbn, b.genre, b.quantity 
                            FROM books b 
                            LEFT JOIN authors a ON b.author_id = a.author_id
                            WHERE b.title LIKE ? OR a.name LIKE ? OR b.isbn LIKE ? OR b.genre LIKE ?");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $books = [];
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    return $books;
}
?>
