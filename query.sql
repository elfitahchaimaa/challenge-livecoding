--premiere requete
SELECT * from books b JOIN borrowings br on b.id=br.id;

--deuxieme requete
SELECT m.name, m.email, COUNT(br.id) AS borrowed_count
FROM members m
JOIN borrowings br ON m.id = br.member_id
WHERE br.return_date IS NULL
GROUP BY m.id, m.name, m.email
HAVING COUNT(br.id) > 3;


--troisieme requete

SELECT b.title, COUNT(br.id) AS borrow_count
FROM books b
JOIN borrowings br ON b.id = br.book_id
GROUP BY b.id, b.title
ORDER BY borrow_count DESC
LIMIT 1;


--quatrieme question

SELECT 
    br.id AS borrowing_id,
    b.title,
    m.name,
    DATEDIFF('2024-03-15', br.due_date) * 0.50 AS late_fee
FROM borrowings br
JOIN books b ON br.book_id = b.id
JOIN members m ON br.member_id = m.id
WHERE br.return_date IS NULL
AND br.due_date < '2024-03-15';

--cinquieme question


SELECT 
    b.title,
    b.available_copies
FROM books b
LEFT JOIN book_authors ba ON b.id = ba.book_id
LEFT JOIN authors a ON ba.author_id = a.id
GROUP BY b.id, b.title, b.available_copies;