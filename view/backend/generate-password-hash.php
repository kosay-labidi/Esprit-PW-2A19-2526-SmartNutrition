<?php
// Script pour générer un hash de mot de passe sécurisé
$motDePasse = 'password'; // Remplacez par le mot de passe que vous voulez
$hash = password_hash($motDePasse, PASSWORD_DEFAULT);

echo "Mot de passe : " . $motDePasse . "\n";
echo "Hash : " . $hash . "\n";
?>
