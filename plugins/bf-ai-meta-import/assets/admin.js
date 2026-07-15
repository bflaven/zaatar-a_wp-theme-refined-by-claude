/* BF AI Meta Import — char counters + pagination page-jump */
(function () {
	'use strict';

	// Live character counters, red above the soft max
	function updateCounter(field) {
		var max = parseInt(field.dataset.max, 10);
		var counter = field.parentNode.querySelector('.bfami-counter');
		if (!counter) {
			return;
		}
		var length = field.value.length;
		counter.textContent = length ? length + ' / ' + max : '';
		counter.classList.toggle('bfami-counter--over', length > max);
	}

	document.querySelectorAll('.bfami-count').forEach(function (field) {
		updateCounter(field);
		field.addEventListener('input', function () {
			updateCounter(field);
		});
	});

	// Dirty tracking on the bulk review table: highlight edited rows,
	// show the edited-post count on the save buttons, warn before
	// leaving the page with unsaved edits.
	var table = document.querySelector('.bfami-table');
	var saveButtons = document.querySelectorAll('.bfami-save');
	var submitting = false;

	function fieldDirty(field) {
		return field.value !== field.defaultValue;
	}

	function refreshDirtyState() {
		var dirtyRows = 0;
		table.querySelectorAll('tbody tr').forEach(function (row) {
			var dirty = Array.prototype.some.call(
				row.querySelectorAll('textarea, input[type="text"]'),
				fieldDirty
			);
			row.classList.toggle('bfami-row-dirty', dirty);
			if (dirty) {
				dirtyRows++;
			}
		});
		saveButtons.forEach(function (button) {
			if (dirtyRows) {
				button.disabled = false;
				button.textContent = button.dataset.labelDirty.replace('%d', String(dirtyRows));
			} else {
				button.disabled = true;
				button.textContent = button.dataset.labelClean;
			}
		});
		return dirtyRows;
	}

	if (table && saveButtons.length) {
		refreshDirtyState();
		table.addEventListener('input', refreshDirtyState);

		table.closest('form').addEventListener('submit', function () {
			submitting = true;
		});
		window.addEventListener('beforeunload', function (event) {
			if (!submitting && refreshDirtyState() > 0) {
				event.preventDefault();
				event.returnValue = '';
			}
		});
	}

	// Copy the visible post IDs (for --ids of the generator script)
	var copyButton = document.getElementById('bfami-copy-ids');
	if (copyButton) {
		copyButton.addEventListener('click', function () {
			var input = document.getElementById('bfami-page-ids');
			input.select();
			var label = copyButton.textContent;
			var restore = function () {
				window.setTimeout(function () { copyButton.textContent = label; }, 1500);
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(input.value).then(function () {
					copyButton.textContent = copyButton.dataset.done;
					restore();
				});
			} else {
				document.execCommand('copy');
				copyButton.textContent = copyButton.dataset.done;
				restore();
			}
		});
	}

	// Page-jump input: Enter navigates to the requested page
	document.querySelectorAll('.bfami-page-jump').forEach(function (input) {
		input.addEventListener('keydown', function (event) {
			if (event.key !== 'Enter') {
				return;
			}
			event.preventDefault();
			var total = parseInt(input.dataset.totalPages, 10) || 1;
			var page = Math.min(Math.max(parseInt(input.value, 10) || 1, 1), total);
			window.location.href = input.dataset.urlTemplate.replace('BFAMI_PAGE', String(page));
		});
	});
})();
