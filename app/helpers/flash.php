<?php
function flash_set(string $msg,string $type='ok'): void { $_SESSION['flash']=['msg'=>$msg,'type'=>$type]; }
function flash_get(): ?array { if(empty($_SESSION['flash'])) return null; $f=$_SESSION['flash']; unset($_SESSION['flash']); return $f; }
