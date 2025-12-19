<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Balasan Pesan Anda</title>
</head>
<body>
    <p>Halo, {{ $kontak->nama }}!</p>
    <p>Terima kasih telah menghubungi Kelurahan Graha Indah. Berikut adalah balasan dari pesan Anda:</p>
    <hr>
    <p><strong>Pesan Anda:</strong></p>
    <blockquote style="background:#f9f9f9;padding:10px;border-left:3px solid #ccc;">{{ $kontak->pesan }}</blockquote>
    <p><strong>Balasan dari Admin:</strong></p>
    <blockquote style="background:#e6f7e6;padding:10px;border-left:3px solid #4caf50;">{{ $balasan }}</blockquote>
    <hr>
    <p>Salam,<br>Admin Kelurahan Graha Indah</p>
</body>
</html>
