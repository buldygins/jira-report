<div class="col-4">
    <table class="table table-bordered">
        <?php foreach ($projects as $project) {
            ?>
            <tr>
                <td>
                    <a href='/project/<?= $this->curr_project->id ?>/desk/<?= $project->jira_key ?>/report'><?= $project->jira_key ?></a>
                </td>

            </tr>
            <?php
        }
        ?>
    </table>
</div>

