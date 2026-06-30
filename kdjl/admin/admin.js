(function () {
	var confirmations = document.querySelectorAll('[data-confirm]');
	for (var i = 0; i < confirmations.length; i++) {
		confirmations[i].onsubmit = function () {
			return window.confirm(this.getAttribute('data-confirm'));
		};
	}
	var selectAll = document.querySelectorAll('[data-select-all]');
	var updateBatch = function (group) {
		var master = document.querySelector('[data-select-all="' + group + '"]');
		var items = document.querySelectorAll('[data-select-item="' + group + '"]');
		var button = document.querySelector('[data-batch-submit="' + group + '"]');
		var checked = 0;
		for (var k = 0; k < items.length; k++) if (items[k].checked) checked++;
		if (master) {
			master.checked = items.length > 0 && checked === items.length;
			master.indeterminate = checked > 0 && checked < items.length;
		}
		if (button) button.disabled = checked === 0;
	};
	for (var j = 0; j < selectAll.length; j++) {
		selectAll[j].onchange = function () {
			var group = this.getAttribute('data-select-all');
			var items = document.querySelectorAll('[data-select-item="' + group + '"]');
			for (var k = 0; k < items.length; k++) items[k].checked = this.checked;
			updateBatch(group);
		};
		updateBatch(selectAll[j].getAttribute('data-select-all'));
	}
	var selectedItems = document.querySelectorAll('[data-select-item]');
	for (var m = 0; m < selectedItems.length; m++) {
		selectedItems[m].onchange = function () { updateBatch(this.getAttribute('data-select-item')); };
	}
}());
