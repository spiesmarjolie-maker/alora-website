<h1>Staff</h1>
<p class="muted">Staff can manage orders, products, categories, codes and delivery. Owners can also change settings and staff.</p>
<div class="cols">
  <section class="panel">
    <table class="tbl">
      <?php foreach ($people as $p): ?>
        <tr><td><strong><?= e($p['name']) ?></strong><br><small class="muted"><?= e($p['email']) ?></small></td><td><?= $p['role'] === 'owner' ? 'Owner' : 'Staff' ?></td>
          <td class="r">
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn--s btn--ghost" name="do" value="reset">Send reset link</button></form>
            <?php if ((int) $p['id'] !== (int) $me['id']): ?>
              <form method="post" class="inline" onsubmit="return confirm('Remove <?= e(addslashes($p['name'])) ?>?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn btn--s btn--danger" name="do" value="remove">Remove</button></form>
            <?php endif ?>
          </td></tr>
      <?php endforeach ?>
    </table>
  </section>
  <form class="panel stack" method="post">
    <?= csrf_field() ?><input type="hidden" name="do" value="add">
    <h2>Add someone</h2>
    <label>Name<input name="name" required></label>
    <label>Email<input name="email" type="email" required></label>
    <label>Role<select name="role"><option value="staff">Staff</option><option value="owner">Owner</option></select></label>
    <p class="muted small">They'll get an email with a link to choose their own password.</p>
    <button class="btn">Add and send invite</button>
  </form>
</div>
