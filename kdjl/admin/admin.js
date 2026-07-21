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
		var buttons = document.querySelectorAll('[data-batch-submit="' + group + '"]');
		var counters = document.querySelectorAll('[data-selected-count="' + group + '"]');
		var checked = 0;
		for (var k = 0; k < items.length; k++) if (items[k].checked) checked++;
		if (master) {
			master.checked = items.length > 0 && checked === items.length;
			master.indeterminate = checked > 0 && checked < items.length;
		}
		for (var b = 0; b < buttons.length; b++) buttons[b].disabled = checked === 0;
		for (var c = 0; c < counters.length; c++) counters[c].innerHTML = checked;
	};
	var updateScopeQuery = function () {
		var scopes = document.querySelectorAll('[data-scope-item]');
		var buttons = document.querySelectorAll('[data-scope-query]');
		var counters = document.querySelectorAll('[data-scope-selected-count]');
		var checked = 0;
		for (var i = 0; i < scopes.length; i++) if (scopes[i].checked) checked++;
		for (var j = 0; j < buttons.length; j++) buttons[j].disabled = checked === 0;
		for (var k = 0; k < counters.length; k++) counters[k].innerHTML = checked;
	};
	var updateDropActions = function () {
		var monsters = document.querySelectorAll('[data-select-item="drop-monsters"]');
		var prop = document.querySelector('[data-drop-prop]:checked');
		var selected = false;
		for (var i = 0; i < monsters.length; i++) if (monsters[i].checked) selected = true;
		var buttons = document.querySelectorAll('[data-drop-action]');
		for (var j = 0; j < buttons.length; j++) buttons[j].disabled = !selected || !prop;
	};
	var syncSelectedMonsters = function () {
		var fields = document.querySelectorAll('[data-selected-gpc-csv]');
		if (!fields.length) return;
		var monsters = document.querySelectorAll('[data-select-item="drop-monsters"]');
		var ids = [];
		for (var i = 0; i < monsters.length; i++) if (monsters[i].checked) ids.push(monsters[i].value);
		for (var j = 0; j < fields.length; j++) fields[j].value = ids.join(',');
	};
	var updateTaskNextList = function () {
		var mode = document.querySelector('[data-task-completion-mode]');
		var sequence = document.querySelector('[data-task-sequence-select]');
		var next = document.querySelector('[data-task-next-select]');
		if (!sequence || !next) return;
		var sequenceEnabled = !mode || mode.value === 'sequence';
		sequence.disabled = !sequenceEnabled;
		next.disabled = !sequenceEnabled;
		if (!sequenceEnabled) return;
		var seq = parseInt(sequence.value, 10) || 0;
		var current = parseInt(next.value, 10) || 0;
		var initial = parseInt(next.getAttribute('data-task-initial-next'), 10) || 0;
		var initialSequence = parseInt(next.getAttribute('data-task-initial-sequence'), 10) || 0;
		if (current < 1 && initial > 0) current = initial;
		var selectedOk = false;
		for (var i = 0; i < next.options.length; i++) {
			var option = next.options[i];
			var value = parseInt(option.value, 10) || 0;
			var xulie = parseInt(option.getAttribute('data-task-xulie'), 10) || 0;
			var visible = value < 1 || (seq > 0 && xulie === seq) || (value === initial && seq === initialSequence);
			option.disabled = !visible;
			option.style.display = visible ? '' : 'none';
			if (value === current && visible) {
				option.selected = true;
				selectedOk = true;
			}
		}
		if (!selectedOk) next.value = '0';
	};
	var taskPickerSources = window.adminTaskPickerSources || {};
	var taskPickerMaps = {};
	var trimText = function (value) {
		return (value || '').replace(/^\s+|\s+$/g, '');
	};
	var sourceMap = function (source) {
		if (taskPickerMaps[source]) return taskPickerMaps[source];
		var rows = taskPickerSources[source] || [];
		var map = {};
		for (var i = 0; i < rows.length; i++) map[parseInt(rows[i].id, 10)] = rows[i].name || '';
		taskPickerMaps[source] = map;
		return map;
	};
	var entityName = function (source, id) {
		id = parseInt(id, 10) || 0;
		var map = sourceMap(source);
		return map[id] ? map[id] : '不存在';
	};
	var entityLabel = function (source, ids) {
		var parts = (ids || '').split('|');
		var labels = [];
		for (var i = 0; i < parts.length; i++) {
			var id = parseInt(parts[i], 10) || 0;
			if (id > 0) labels.push('id=' + id + ' ' + entityName(source, id));
		}
		return labels.length ? labels.join(' / ') : '未选择';
	};
	var parseTaskRows = function (picker) {
		var store = picker.querySelector('[data-task-picker-value]');
		var mode = picker.getAttribute('data-task-picker');
		var value = store ? store.value : '';
		var rows = [];
		if (mode === 'count') {
			value = value.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
			var lines = value.split('\n');
			for (var i = 0; i < lines.length; i++) {
				var line = trimText(lines[i]);
				if (line === '') continue;
				var match = line.match(/^([0-9|]+):([0-9]+)$/);
				if (match) rows.push({ ids: match[1], count: match[2] });
			}
		} else {
			var ids = value.split('|');
			for (var j = 0; j < ids.length; j++) {
				var id = parseInt(ids[j], 10) || 0;
				if (id > 0) rows.push({ ids: String(id), count: '' });
			}
		}
		return rows;
	};
	var syncTaskRows = function (picker) {
		var store = picker.querySelector('[data-task-picker-value]');
		if (!store) return;
		var rows = picker._taskRows || [];
		var mode = picker.getAttribute('data-task-picker');
		var values = [];
		for (var i = 0; i < rows.length; i++) {
			if (mode === 'count') values.push(rows[i].ids + ':' + rows[i].count);
			else values.push(rows[i].ids);
		}
		store.value = mode === 'count' ? values.join('\n') : values.join('|');
	};
	var renderTaskRows = function (picker) {
		var list = picker.querySelector('[data-task-picker-list]');
		if (!list) return;
		var source = picker.getAttribute('data-task-source');
		var mode = picker.getAttribute('data-task-picker');
		var rows = picker._taskRows || [];
		list.innerHTML = '';
		if (!rows.length) {
			var empty = document.createElement('div');
			empty.className = 'task-picker-empty';
			empty.appendChild(document.createTextNode('未配置'));
			list.appendChild(empty);
			return;
		}
		for (var i = 0; i < rows.length; i++) {
			(function (index) {
				var row = rows[index];
				var item = document.createElement('div');
				item.className = 'task-picker-row';
				var text = document.createElement('div');
				text.className = 'task-picker-row-text';
				var strong = document.createElement('strong');
				strong.appendChild(document.createTextNode(entityLabel(source, row.ids)));
				var small = document.createElement('span');
				small.appendChild(document.createTextNode(mode === 'count' ? ('数量 ' + row.count) : ('id=' + row.ids)));
				text.appendChild(strong);
				text.appendChild(small);
				var actions = document.createElement('div');
				actions.className = 'task-picker-row-actions';
				var edit = document.createElement('button');
				edit.className = 'btn secondary';
				edit.type = 'button';
				edit.appendChild(document.createTextNode('编辑'));
				edit.onclick = function () { openTaskPicker(picker, index); };
				var remove = document.createElement('button');
				remove.className = 'btn danger';
				remove.type = 'button';
				remove.appendChild(document.createTextNode('删除'));
				remove.onclick = function () {
					picker._taskRows.splice(index, 1);
					syncTaskRows(picker);
					renderTaskRows(picker);
				};
				actions.appendChild(edit);
				actions.appendChild(remove);
				item.appendChild(text);
				item.appendChild(actions);
				list.appendChild(item);
			}(i));
		}
	};
	var taskPickerModal = document.querySelector('[data-task-picker-modal]');
	var activeTaskPicker = null;
	var activeTaskRow = -1;
	var activeTaskSelection = null;
	var taskPickerIsMulti = function (picker) {
		return picker && picker.getAttribute('data-task-multi') === '1';
	};
	var selectedTaskIds = function () {
		var ret = {};
		if (!activeTaskSelection || !activeTaskSelection.ids) return ret;
		var parts = String(activeTaskSelection.ids).split('|');
		for (var i = 0; i < parts.length; i++) {
			var id = parseInt(parts[i], 10) || 0;
			if (id > 0) ret[id] = true;
		}
		return ret;
	};
	var setTaskPickerSelection = function (ids, name, replace) {
		if (taskPickerIsMulti(activeTaskPicker) && !replace) {
			var map = selectedTaskIds();
			var parts = String(ids || '').split('|');
			for (var i = 0; i < parts.length; i++) {
				var id = parseInt(parts[i], 10) || 0;
				if (id < 1) continue;
				if (map[id]) delete map[id];
				else map[id] = true;
			}
			var selected = [];
			for (var key in map) if (map.hasOwnProperty(key)) selected.push(parseInt(key, 10));
			selected.sort(function (a, b) { return a - b; });
			activeTaskSelection = selected.length ? { ids: selected.join('|'), name: '' } : null;
		} else {
			activeTaskSelection = ids ? { ids: String(ids), name: name || '' } : null;
		}
		var selected = taskPickerModal ? taskPickerModal.querySelector('[data-task-picker-selected]') : null;
		if (selected) {
			selected.innerHTML = '';
			selected.appendChild(document.createTextNode(activeTaskSelection ? ('已选择：' + entityLabel(activeTaskPicker.getAttribute('data-task-source'), activeTaskSelection.ids)) : '未选择'));
		}
		renderTaskPickerResults();
	};
	var renderTaskPickerResults = function () {
		if (!taskPickerModal || !activeTaskPicker) return;
		var source = activeTaskPicker.getAttribute('data-task-source');
		var input = taskPickerModal.querySelector('[data-task-picker-search]');
		var results = taskPickerModal.querySelector('[data-task-picker-results]');
		var term = trimText(input ? input.value : '').toLowerCase();
		var rows = taskPickerSources[source] || [];
		var selected = selectedTaskIds();
		var shown = 0;
		results.innerHTML = '';
		for (var i = 0; i < rows.length; i++) {
			var id = parseInt(rows[i].id, 10) || 0;
			var name = rows[i].name || '';
			var hay = (String(id) + ' ' + name).toLowerCase();
			if (term !== '' && hay.indexOf(term) === -1) continue;
			if (term === '' && shown >= 80) break;
			if (shown >= 120) break;
			(function (rowId, rowName) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = selected[rowId] ? 'task-picker-result active' : 'task-picker-result';
				var strong = document.createElement('strong');
				strong.appendChild(document.createTextNode(rowName));
				var span = document.createElement('span');
				span.appendChild(document.createTextNode('id=' + rowId));
				btn.appendChild(strong);
				btn.appendChild(span);
				btn.onclick = function () { setTaskPickerSelection(rowId, 'id=' + rowId + ' ' + rowName, false); };
				results.appendChild(btn);
			}(id, name));
			shown++;
		}
		if (!shown) {
			var empty = document.createElement('div');
			empty.className = 'task-picker-empty';
			empty.appendChild(document.createTextNode('没有匹配结果'));
			results.appendChild(empty);
		}
	};
	var closeTaskPicker = function () {
		if (taskPickerModal) taskPickerModal.setAttribute('hidden', 'hidden');
		activeTaskPicker = null;
		activeTaskRow = -1;
		activeTaskSelection = null;
	};
	var openTaskPicker = function (picker, index) {
		if (!taskPickerModal) return;
		activeTaskPicker = picker;
		activeTaskRow = index;
		var rows = picker._taskRows || [];
		var row = index >= 0 ? rows[index] : false;
		var title = taskPickerModal.querySelector('[data-task-picker-title]');
		var search = taskPickerModal.querySelector('[data-task-picker-search]');
		var countField = taskPickerModal.querySelector('[data-task-picker-count-field]');
		var countInput = taskPickerModal.querySelector('[data-task-picker-count]');
		var label = picker.getAttribute('data-task-label') || '选择';
		var mode = picker.getAttribute('data-task-picker');
		if (title) {
			title.innerHTML = '';
			title.appendChild(document.createTextNode((index >= 0 ? '编辑' : '添加') + label));
		}
		if (search) search.value = '';
		if (countField) countField.style.display = mode === 'count' ? '' : 'none';
		if (countInput) countInput.value = row && row.count ? row.count : '1';
		if (row) setTaskPickerSelection(row.ids, entityLabel(picker.getAttribute('data-task-source'), row.ids), true);
		else setTaskPickerSelection('', '', true);
		taskPickerModal.removeAttribute('hidden');
		renderTaskPickerResults();
		if (search) search.focus();
	};
	var applyTaskPicker = function () {
		if (!activeTaskPicker || !activeTaskSelection) {
			window.alert('请先选择一项。');
			return;
		}
		var mode = activeTaskPicker.getAttribute('data-task-picker');
		var rows = activeTaskPicker._taskRows || [];
		var row = { ids: activeTaskSelection.ids, count: '' };
		if (mode === 'count') {
			var countInput = taskPickerModal.querySelector('[data-task-picker-count]');
			var count = parseInt(countInput ? countInput.value : '0', 10) || 0;
			if (count < 1) {
				window.alert('数量必须大于 0。');
				return;
			}
			row.count = String(count);
		} else {
			for (var i = 0; i < rows.length; i++) {
				if (i !== activeTaskRow && rows[i].ids === row.ids) {
					window.alert('已经添加过这个 id。');
					return;
				}
			}
		}
		if (activeTaskRow >= 0) rows[activeTaskRow] = row;
		else rows.push(row);
		activeTaskPicker._taskRows = rows;
		syncTaskRows(activeTaskPicker);
		renderTaskRows(activeTaskPicker);
		closeTaskPicker();
	};
	var taskPickers = document.querySelectorAll('[data-task-picker]');
	for (var tp = 0; tp < taskPickers.length; tp++) {
		taskPickers[tp]._taskRows = parseTaskRows(taskPickers[tp]);
		renderTaskRows(taskPickers[tp]);
		(function (picker) {
			var add = picker.querySelector('[data-task-picker-add]');
			if (add) add.onclick = function () { openTaskPicker(picker, -1); };
		}(taskPickers[tp]));
	}
	if (taskPickerModal) {
		var pickerSearch = taskPickerModal.querySelector('[data-task-picker-search]');
		if (pickerSearch) {
			pickerSearch.onkeyup = renderTaskPickerResults;
			pickerSearch.oninput = renderTaskPickerResults;
		}
		var pickerClose = taskPickerModal.querySelector('[data-task-picker-close]');
		if (pickerClose) pickerClose.onclick = closeTaskPicker;
		var pickerApply = taskPickerModal.querySelector('[data-task-picker-apply]');
		if (pickerApply) pickerApply.onclick = applyTaskPicker;
	}
	var prepareDropSubmit = function () {
		syncSelectedMonsters();
		var monsters = document.querySelectorAll('[data-select-item="drop-monsters"]');
		for (var i = 0; i < monsters.length; i++) monsters[i].removeAttribute('name');
		return true;
	};
	for (var j = 0; j < selectAll.length; j++) {
		selectAll[j].onchange = function () {
			var group = this.getAttribute('data-select-all');
			var items = document.querySelectorAll('[data-select-item="' + group + '"]');
			for (var k = 0; k < items.length; k++) items[k].checked = this.checked;
			updateBatch(group);
			updateScopeQuery();
			updateDropActions();
			syncSelectedMonsters();
		};
		updateBatch(selectAll[j].getAttribute('data-select-all'));
	}
	var selectedItems = document.querySelectorAll('[data-select-item]');
	for (var m = 0; m < selectedItems.length; m++) {
		selectedItems[m].onchange = function () {
			updateBatch(this.getAttribute('data-select-item'));
			updateScopeQuery();
			updateDropActions();
			syncSelectedMonsters();
		};
	}
	var dropProps = document.querySelectorAll('[data-drop-prop]');
	for (var n = 0; n < dropProps.length; n++) dropProps[n].onchange = updateDropActions;
	var scopeFilters = document.querySelectorAll('[data-scope-filter]');
	for (var o = 0; o < scopeFilters.length; o++) {
		var filterScopes = function () {
			var term = this.value.toLowerCase().replace(/^\s+|\s+$/g, '');
			var options = document.querySelectorAll('[data-scope-option]');
			for (var i = 0; i < options.length; i++) {
				var text = (options[i].getAttribute('data-scope-search') || '').toLowerCase();
				options[i].style.display = term === '' || text.indexOf(term) !== -1 ? '' : 'none';
			}
		};
		scopeFilters[o].onkeyup = filterScopes;
		scopeFilters[o].oninput = filterScopes;
	}
	var confirmActions = document.querySelectorAll('[data-confirm-action]');
	for (var p = 0; p < confirmActions.length; p++) {
		confirmActions[p].onclick = function () { return window.confirm(this.getAttribute('data-confirm-action')); };
	}
	var taskSequence = document.querySelector('[data-task-sequence-select]');
	if (taskSequence) taskSequence.onchange = updateTaskNextList;
	var taskCompletionMode = document.querySelector('[data-task-completion-mode]');
	if (taskCompletionMode) taskCompletionMode.onchange = updateTaskNextList;
	var dropForms = document.querySelectorAll('[data-drop-form]');
	for (var q = 0; q < dropForms.length; q++) dropForms[q].onsubmit = prepareDropSubmit;
	updateScopeQuery();
	updateDropActions();
	syncSelectedMonsters();
	updateTaskNextList();
}());
