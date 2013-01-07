<div class="contacts index">
	<h2><?php echo __('Contacts'); ?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo 'Id'; ?></th>
			<th><?php echo 'Name'; ?></th>
			<th><?php echo 'Email'; ?></th>
			<th><?php echo 'MailingStreet'; ?></th>
			<th><?php echo 'MailingCity'; ?></th>
			<th><?php echo 'MailingState'; ?></th>
			<th><?php echo 'MailingPostalCode'; ?></th>
			<th class="actions"><?php echo __('Actions'); ?></th>
	</tr>
	<?php
	foreach ($contacts as $contact): ?>
	<tr>
		<td><?php echo h($contact['Id']); ?>&nbsp;</td>
		<td><?php echo h($contact['Name']); ?></td>
		<td><?php echo h($contact['Email']); ?></td>
		<td><?php echo h($contact['MailingStreet']); ?></td>
		<td><?php echo h($contact['MailingCity']); ?>&nbsp;</td>
		<td><?php echo h($contact['MailingState']); ?>&nbsp;</td>
		<td><?php echo h($contact['MailingPostalCode']); ?></td>
		<td class="actions">
			<?php echo $this->Html->link(__('View'), array('action' => 'view', $contact['Id'])); ?>
			<?php echo $this->Html->link(__('Edit'), array('action' => 'edit', $contact['Id'])); ?>
			<?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $contact['Id']), null, __('Are you sure you want to delete # %s?', $contact['Id'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
	</table>

</div>
