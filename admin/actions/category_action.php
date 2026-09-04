<?php
/** POST-only: create, update, or delete a category. Any signed-in role (creator+), same tier as video management. */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

$admin = require_admin();

if (!is_post()) {
    redirect('/admin/categories.php');
}
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    flash_set('category_error', 'Your session expired — please try that again.');
    redirect('/admin/categories.php');
}

$pdo = db();
$action = $_POST['action'] ?? '';

/** Generate a unique slug, appending -2, -3, ... on collision. */
function unique_category_slug(PDO $pdo, string $base, ?int $excludeId = null): string
{
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = ?' . ($excludeId ? ' AND id != ?' : '');
        $params = $excludeId ? [$slug, $excludeId] : [$slug];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

if ($action === 'delete') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            // Videos in this category aren't deleted — category_id just
            // goes NULL (ON DELETE SET NULL) and they show as "General".
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash_set('category_success', 'Deleted "' . $row['name'] . '". Its videos are now uncategorized.');
        }
    }
    redirect('/admin/categories.php');
}

if (!in_array($action, ['create', 'update'], true)) {
    redirect('/admin/categories.php');
}

$name = trim((string) ($_POST['name'] ?? ''));
$slugInput = trim((string) ($_POST['slug'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));

$errors = [];
if ($name === '' || mb_strlen($name) > 100) {
    $errors[] = 'Name is required (max 100 characters).';
}
if (mb_strlen($description) > 500) {
    $errors[] = 'Description must be under 500 characters.';
}

$id = null;
if ($action === 'update') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        redirect('/admin/categories.php');
    }
    $exists = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
    $exists->execute([$id]);
    if ((int) $exists->fetchColumn() === 0) {
        flash_set('category_error', 'That category no longer exists.');
        redirect('/admin/categories.php');
    }
}

if (!$errors) {
    $slugBase = slugify($slugInput !== '' ? $slugInput : $name);
    $slug = unique_category_slug($pdo, $slugBase, $id);
}

if ($errors) {
    flash_set('category_error', implode(' ', $errors));
    redirect('/admin/categories.php' . ($id ? '?edit=' . $id : '?new=1'));
}

if ($action === 'create') {
    $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)');
    $stmt->execute([$name, $slug, $description ?: null]);
    flash_set('category_success', 'Added category "' . $name . '".');
} else {
    $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?');
    $stmt->execute([$name, $slug, $description ?: null, $id]);
    flash_set('category_success', 'Saved changes to "' . $name . '".');
}

redirect('/admin/categories.php');
