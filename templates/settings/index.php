<div class="container">
    <form class="form" action="" method="post">
        <?php
        $i = 0;
        foreach ($settings as $setting):
            if ($i == 0) {
                ?>
                <div class="form-row">
            <?php }
            $i++;
            ?>
            <div class="col">
                <?= $setting ?>
            </div>
            <?php if ($i % 3 == 0) {
            ?>
            </div>
            <?php
            $i = 0;
        } ?>
        <?php endforeach;
        if (count($settings) % 3 != 0){ ?>
          </div>
        <?php } ?>
<a href="costRate" class="btn btn-secondary" id="addCostRate">Добавить вид оплаты</a>
<input type="submit" class="btn btn-primary" value="Сохранить">
</form>
<form class="form" action="" method="post">
    <input type="hidden" name="_method" value="DELETE">
    <input type="submit" value="Удалить" class="btn btn-danger">
</form>
</div>
