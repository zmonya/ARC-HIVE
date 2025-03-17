<div class="table-container">
    <h3>Department Management</h3>
    <table>
        <thead>
            <tr>
                <th>Department Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($departments as $department): ?>
                <tr>
                    <td><?= htmlspecialchars($department['name']) ?></td>
                    <td class="action-buttons">
                        <button class="edit-btn" onclick="editDepartment(<?= $department['id'] ?>)">Edit</button>
                        <button class="delete-btn" onclick="deleteDepartment(<?= $department['id'] ?>)">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>