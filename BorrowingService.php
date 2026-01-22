<?php

class BorrowingService{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

public function borrowBook(int $bookId,int $memberId,string $borrowDate):array{
    try {
    $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT available_copies FROM books WHERE id = ?");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();

            if(!$book){
                throw new Exception("book not found");
            }
            if ($book['available_copies'] <= 0) {
                throw new Exception("No available copies");
            }

            $stmt = $this->db->prepare("SELECT member_type FROM members WHERE id = ?");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();

            if(!$member){
                throw new exception("member not found");
            }
             $limit = ($member['member_type'] === 'faculty') ? 10 : 3;

          
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM borrowings 
                 WHERE member_id = ? AND return_date IS NULL"
            );
            $stmt->execute([$memberId]);
            $count = $stmt->fetchColumn();

            if ($count >= $limit) {
                throw new Exception("Borrowing limit exceeded");
            }

            
            $dueDate = date('Y-m-d', strtotime($borrowDate . ' +14 days'));

            $stmt = $this->db->prepare(
                "INSERT INTO borrowings (book_id, member_id, borrow_date, due_date)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$bookId, $memberId, $borrowDate, $dueDate]);

            $borrowingId = $this->db->lastInsertId();

            
            $stmt = $this->db->prepare(
                "UPDATE books SET available_copies = available_copies - 1 WHERE id = ?"
            );
            $stmt->execute([$bookId]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Book borrowed successfully',
                'borrowing_id' => $borrowingId
            ];
}catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
}
 public function returnBook(int $borrowingId,string $returnDate):float{
    $stmt = $this->db->prepare(
            "SELECT book_id, due_date FROM borrowings 
             WHERE id = ? AND return_date IS NULL"
        );
    $stmt->execute([$borrowingId]);
    $borrowing = $stmt->fetch();

    if (!$borrowing) {
            return 0;
        }

    $lateFee = 0;
     if ($returnDate > $borrowing['due_date']) {
            $daysLate = (strtotime($returnDate) - strtotime($borrowing['due_date'])) / 86400;
            $lateFee = $daysLate * 0.50;
        }

    $stmt = $this->db->prepare(
            "UPDATE borrowings 
             SET return_date = ?, late_fee = ?
             WHERE id = ?"
    );

        $stmt->execute([$returnDate, $lateFee, $borrowingId]);

        $stmt = $this->db->prepare(
            "UPDATE books SET available_copies = available_copies + 1 WHERE id = ?"
        );
        $stmt->execute([$borrowing['book_id']]);

        return $lateFee;

 }


}
?>