<div class="col-4">
    <a href="/project/create">Создать проект</a>
    <table class="table table-bordered">
        <?php foreach ($projects as $project) { ?>
            <tr>
                <td>
                    <a href='/project/<?= $project->id ?>/report'><?= $project->name ?></a>
                    <?= $project->descr ?>
                </td>
                <td>
                    <a href="/project/<?= $project->id ?>/settings">Настройки</a>
                </td>
                <td>
                    <a href="/project/<?= $project->id ?>/sync">Синхронизировать</a>
                </td>
            </tr>
            <?php } ?>
    </table>
</div>

