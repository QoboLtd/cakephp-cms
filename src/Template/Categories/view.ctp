<section class="content-header">
    <h1><?= h($category->site->name) . ' &raquo; ' . h($category->name) ?></h1>
</section>
<section class="content">
    <?= $this->element('Categories/new_articles', [
        'categories' => $categories,
        'site' => $category->site,
        'article' => $article,
        'articleTypes' => $types
    ]) ?>
    <?= $this->element('Categories/articles', [
        'articles' => $category->articles,
        'articleTypes' => $types
    ]) ?>
</section>