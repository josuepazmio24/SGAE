<?php
class View {
public static function e(?string $str): string {
return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
}