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
