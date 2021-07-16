<div class="container">
    <form class="form" action="" method="post">
        <?php foreach ($settings as $setting):?>
            <?= $setting ?>
        <?php endforeach; ?>
        <input type="submit"  class="btn btn-primary" value="Сохранить">
    </form>
    <form class="form" action="" method="post">
        <input type="hidden" name="_method" value="DELETE">
        <input type="submit" value="Удалить" class="btn btn-danger">
    </form>
</div>
