<div class="col-4">
    <table class="table table-bordered">
        <?php foreach ($projects as $project) {
            ?>
            <tr>
                <td>
                    <a href='/getDesk?project=<?= $_GET['project'] ?>&desk=<?= $project->key ?>'><?= $project->name ?> ( <?= $project->key ?> )</a>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>

