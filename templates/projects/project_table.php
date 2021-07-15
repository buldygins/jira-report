<div class="col-4">
    <table class="table table-bordered">
        <?php foreach ($projects as $project) {
            ?>
            <tr>
                <td>
                    <a href='/run?project=<?= $project['nam'] ?>'><?= $project['descr'] ?></a>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
</div>

