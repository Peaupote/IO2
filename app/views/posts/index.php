<div class="row">
    <div class="small-offset-2 small-8">
        <h2>Tous les posts</h2>
        <hr>
    </div>
    <?php foreach($posts as $post): ?>
        <article class="small-offset-2 small-8">
            <h3><?= $post->name ?></h3>
            <p><?= $post->content ?></p>
        </article>
    <?php endforeach ?>
</div>