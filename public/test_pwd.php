<?php
$pwd = '123456';
$hash = '$2y$10$EIXChIMz.k69QSCs5wV1sOQF/5Y8dfrGQf.1T9HeN1bWZvh8gvl16'; // 123456
var_dump(password_verify($pwd, $hash));
