<?php
function user_display_name(): string {
  $fn = $_SESSION['first_name'] ?? '';
  $ln = $_SESSION['last_name']  ?? '';
  return trim($fn . ' ' . $ln);
}
