<table class="table table_bordered">
    <thead>
    <th></th>
    <?php
    for ($i = 1; $i < $days; $i++):?>
        <th><?= $i ?></th>
    <?php endfor; ?>
    <th>Всего</th>
    </thead>
    <tbody>
    <?php foreach ($tasks as $task):
        ?>
        <tr>
            <td><?= $task->project_key ?> <?= $task->summary ?></td>
            <?php for ($i = 1; $i < $days; $i++):
                $task_time = 0;
                if (isset($time[$task->project_key][$i])) {
                    $time = intdiv($time[$task->project_key][$i], 3600);
                    $task_time += $time[$task->project_key][$i];
                    ?>
                    <td class=""><?= $time ?>ч</td>
                <?php } else {
                    echo '<td></td>';
                }endfor; ?>
        </tr>
    <?php
    endforeach;
    ?>
    </tbody>
</table>
