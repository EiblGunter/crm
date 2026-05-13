const fs = require('fs');
let file = fs.readFileSync('c:/Users/eiblg/Desktop/antigravity/Projekte/my_php_local/httpdocs/tools/form/form_multiline/form_multiline.php', 'utf8');

const newBuildFieldsTable = `GridApp.prototype.buildFieldsTable = function () {
    var self = this;
    var h = '<table class="table table-sm table-hover"><thead><tr><th>Feld</th><th>Label</th><th class="text-center">Sort</th><th class="text-center">Req</th><th class="text-center">RO</th><th class="text-center" title="Auf Handy anzeigen (Klick auf Icon umzuschalten)"><i class="fas fa-mobile-alt" style="cursor:pointer;" onclick="$(\\\'.cfg-f-mob\\\').each(function(){ $(this).prop(\\\'checked\\\', !$(this).prop(\\\'checked\\\')); }); window.getGrid(\\\'' + self.containerId + '\\\').scrapeConfigFromUI(); window.getGrid(\\\'' + self.containerId + '\\\').saveConfig();"></i></th><th></th></tr></thead><tbody>';
    this.config.fields.forEach(function (f, i) {
        var isSort = (self.sortField === f.fieldName);
        h += '<tr data-idx="' + i + '">';
        h += '<td><small class="font-weight-bold">' + f.fieldName + '</small></td>';
        h += '<td><input class="form-control form-control-sm cfg-f-label" value="' + (f.label || f.fieldName) + '"></td>';
        h += '<td class="text-center"><div class="d-flex justify-content-center align-items-center"><input type="radio" name="def_sort" class="cfg-f-sort mr-1" value="' + f.fieldName + '" ' + (isSort ? 'checked' : '') + '><input type="checkbox" class="cfg-f-desc" ' + (isSort && self.sortOrder == 'DESC' ? 'checked' : '') + '></div></td>';
        h += '<td class="text-center"><input type="checkbox" class="cfg-f-req" ' + (f.required ? 'checked' : '') + '></td>';
        h += '<td class="text-center"><input type="checkbox" class="cfg-f-ro" ' + (f.readonly ? 'checked' : '') + '></td>';
        h += '<td class="text-center" title="Auf Handy anzeigen"><input type="checkbox" class="cfg-f-mob" ' + (f.hide_on_mobile ? '' : 'checked') + '></td>';
        h += '<td class="text-right"><button class="btn btn-sm btn-light border" onclick="window.getGrid(\\\'' + self.containerId + '\\\').editFieldSettings(' + i + ')"><i class="fas fa-cog"></i></button></td>';
        h += '</tr>';
    });
    h += '</tbody></table>';
    h += '<div class="mt-3 text-center"><button class="btn btn-sm btn-outline-secondary" onclick="window.getGrid(\\\'' + self.containerId + '\\\').addCustomField()"><i class="fas fa-plus"></i> Benutzerdefiniertes Feld hinzufügen</button></div>';
    return h;
};`;

const startIdx = file.indexOf('GridApp.prototype.buildFieldsTable = function () {');
const endIdx = file.indexOf('};', startIdx) + 2;

file = file.substring(0, startIdx) + newBuildFieldsTable + file.substring(endIdx);
fs.writeFileSync('c:/Users/eiblg/Desktop/antigravity/Projekte/my_php_local/httpdocs/tools/form/form_multiline/form_multiline.php', file, 'utf8');

console.log('Successfully replaced buildFieldsTable using NodeJS safely.');
