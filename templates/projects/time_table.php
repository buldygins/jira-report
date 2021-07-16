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
        $task_time = 0;
        ?>
        <tr>
            <td><?= $task->project_key ?> <?= $task->summary ?></td>
            <?php for ($i = 1; $i < $days; $i++):

                if (isset($time[$task->project_key][$i])) {
                    $task_time += $time[$task->project_key][$i];
                    ?>
                    <td class=""><?= $time[$task->project_key][$i] ?></td>
                <?php } else {
                    echo '<td></td>';
                }endfor; ?>
            <td><?= $time[$task->project_key]['all'] ?></td>
        </tr>
    <?php
    endforeach;
    ?>
    <tr>
        <td>Всего за день</td>
        <?php for ($i = 1; $i < $days; $i++): ?>
            <td class=""><?= $time[$i]['all'] ?></td>
        <?php endfor; ?>
    </tr>
    </tbody>
</table>
