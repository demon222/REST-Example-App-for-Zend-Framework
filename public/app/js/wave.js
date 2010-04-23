function esc(str) {
    if ("string" != typeof(str)) {
        return str;
    }
    str = str.replace(/&/g, "&amp;");
    str = str.replace(/"/g, "&quot;");
    str = str.replace(/'/g, "&#039;");
    str = str.replace(/</g, "&lt;");
    str = str.replace(/>/g, "&gt;");
    return str;
}

document.body.style.backgroundImage = 'url(' + uki.theme.imageSrc('body') + ')';
var UsersRender = {
    template: uki.theme.template('usersRender'),
    render: function(data, rect, i) {
        return uki.extend(this.template, [
            undefined, uki.theme.imageSrc('unknown'),
            undefined, esc(data.username),
            undefined, esc(data.username)
        ]).join('');
    },
    setSelected: function(container, data, state, focus) {
        container.style.backgroundColor = state && focus ? '#E0E8A4' : state ? '#CCC' : '';
    }
};
var CommunitiesRender = {
    template: uki.theme.template('communitiesRowRender'),
    render: function(data, rect, i) {
        return uki.extend(this.template, [
            undefined, esc(data.id),
            undefined, uki.theme.imageSrc('unknown'),
            undefined, esc(data.title)
        ]).join('');
    },
    setSelected: function(container, data, state, focus) {
        container.style.backgroundColor = state && focus ? '#E0E8A4' : state ? '#CCC' : '';
    }
};
var DiscussionsRender = {
    template: uki.theme.template('discussionsRowRender'),
    render: function(data, rect, i) {
        return uki.extend(this.template, [
            undefined, data.id,
            undefined, uki.theme.imageSrc('unknown'),
            undefined, esc(data.RecentEntry_Creator_username),
            undefined, esc(data.title),
            undefined, esc(data.title),
            undefined, esc(data.RecentEntry_modified)
        ]).join('');
    },
    setSelected: function(container, data, state, focus) {
        container.style.backgroundColor = state && focus ? '#E0E8A4' : state ? '#CCC' : '';
    }
};
var EntriesRender = {
    template: uki.theme.template('entriesRowRender'),
    render: function(data, rect) {
        return uki.extend(this.template, [
            undefined, uki.theme.imageSrc('unknown'),
            undefined, esc(data.Creator_username),
            undefined, esc(data.modified),
            undefined, data.comment
        ]).join('');
    }
};

function toolbarButton (label, iconOffset) {
    if (null == iconOffset) {
        iconOffset = new uki.geometry.Rect(0, 16);
    }
    var html = uki.extend(uki.theme.template('toolbarButton', {height: 24, size: new uki.geometry.Size(16, 16)}), [label, undefined, 'url('+uki.theme.imageSrc('icons-sprite')+') ' + iconOffset]);
    return { html: html.join(''), inset: '0 8 0 28', style: { fontWeight: 'normal', fontSize: '11px', textAlign: 'left', color: '' } };
}

function menuButton (label, iconOffset, rect, id) {
    return uki.extend(toolbarButton(label, iconOffset), { view: 'Button', id: id, style: { textAlign: 'left', fontSize: '13px', fontWeight: 'normal', color: '' }, rect: rect, backgroundPrefix: 'link-button-', focusable: 'false', anchors: 'left top right', inset: '0 8 0 30', focusable: false });
}

function panel (labelText, args) {
    args.childViews = (args.childViews || []).concat({ view: 'Label', rect: '7 6 100 13', anchors: 'left top right', text: labelText, style: { color: '#FFF', fontSize: '13px' } });
    return uki.extend({}, { view: 'Box', name: 'panel', background: 'theme(panel-blue)', rect: '100 100', anchors: 'left top right bottom' }, args);
}

uki({ view: 'HSplitPane', id: 'splitMain', rect: '15 15 970 970', minSize: '800 400', anchors: 'left top right bottom', handleWidth: 15, handlePosition: 166, leftMin: 166, rightMin: 600,
    leftChildViews: { view: 'VSplitPane', id: 'splitLeft', rect: '166 970', anchors: 'left top right bottom', handleWidth: 15, handlePosition: 152, bottomMin: 300, topMin: 152,
        topChildViews: panel('Navigation', { rect: '166 152', background: 'theme(panel-white)', childViews: [
            { view: 'ScrollPane', rect: '0 24 166 122', anchors: 'left top right bottom', childViews: [
                { view: 'VFlow', rect: '0 0 166 120', anchors: 'left top right', childViews: [
                    menuButton('All', '-160px 0', '166 24', 'nav-all'),
                    menuButton('My Interests', '-176px 0', '166 24', 'nav-interests'),
                    menuButton('By me', '-192px 0', '166 24', 'nav-by_me'),
                    menuButton('Settings', '-240px 0', '166 24', 'nav-settings'),
                    menuButton('Log Out', null, '166 24', 'nav-log_out')
                ] }
            ] }
        ]}),
        bottomChildViews: panel('Participants', { rect: '166 803', childViews: [
            { view: 'Box', rect: '0 23 166 70', background: 'theme(box-lblue-top)', anchors: 'left top right', childViews: [
                { view: 'Image', rect: '7 6 27 27', anchors: 'left top', src: uki.theme.imageSrc('unknown'), background: 'theme(thumb)' },
                { view: 'Label', rect: '40 8 100 13', anchors: 'left top', text: 'Volodya', style: { fontWeight: 'bold', fontSize: '13px' } },
                { view: 'TextField', rect: '15 41 122 24', anchors: 'left top right', style: { fontSize: '12px' }, backgroundPrefix: 'search-', value: '', placeholder: 'Search participants' },
                { view: 'Button', rect: '140 46 13 13', anchors: 'right top', backgroundPrefix: 'search-button-', focusable: false }
            ] },
            { view: 'ScrollPane', rect: '0 93 166 706', anchors: 'left top right bottom', childViews: [
                { view: 'List', id: "users-list", rect: '166 0', anchors: 'left top right', minSize: '0 100', textSelectable: false, rowHeight: 34, render: UsersRender }
            ] }
        ] })
    },
    rightChildViews: { view: 'HSplitPane', id: 'splitRight', rect: '789 970', anchors: 'left top right bottom', handleWidth: 15, handlePosition: 300, leftMin: 300, rightMin: 300,
        leftChildViews: { view: 'VSplitPane', rect: '166 970', anchors: 'left top right bottom', handleWidth: 15, handlePosition: 300, bottomMin: 300, topMin: 152,
            topChildViews: panel('Communities', { rect: '300 300', childViews: [
                { view: 'Box', rect: '0 23 300 32', background: 'theme(box-lblue)', anchors: 'left top right', childViews: [
                    { view: 'TextField', rect: '15 4 255 24', anchors: 'left top right', style: { fontSize: '12px' }, backgroundPrefix: 'search-', value: '', placeholder: 'Search communities' },
                    { view: 'Button', rect: '274 9 13 13', anchors: 'right top', backgroundPrefix: 'search-button-', focusable: false }
                ] },
                { view: 'Toolbar', rect: '0 55 300 24', anchors: 'left top right', background: 'theme(toolbar-normal)', buttons: [
                    toolbarButton('Join', '-112px 0'), toolbarButton('Follow', '0 0'), toolbarButton('Delete', '-96px 0'), toolbarButton('Invite', '0 0')
                ] },
                { view: 'ScrollPane', rect: '0 79 300 193', anchors: 'left top right bottom', childViews: [
                    { view: 'List', id: 'communities-list', rect: '300 0', anchors: 'left top right', background: 'none', textSelectable: false, rowHeight: 38, render: CommunitiesRender }
                ] },
                { view: 'Box', rect: '0 272 300 24', background: 'theme(box-lblue-bottom)', anchors: 'left bottom right', childViews: [
                    { view: 'Button', rect: '271 5 24 18', backgroundPrefix: 'plus-button-', anchors: 'right bottom', focusable: false }
                ] }
            ] } ),
            bottomChildViews: panel('Discussions', { rect: '300 655', childViews: [
                { view: 'Box', rect: '0 23 300 32', background: 'theme(box-lblue)', anchors: 'left top right', childViews: [
                    { view: 'TextField', id: 'discussions-search', rect: '14 4 255 24', anchors: 'left top right', style: { fontSize: '12px' }, backgroundPrefix: 'search-', value: '', placeholder: 'Search discussions' },
                    { view: 'Button', id: 'discussions-search_launch', rect: '274 9 13 13', anchors: 'right top', backgroundPrefix: 'search-button-', focusable: false }
                ] },
                { view: 'Toolbar', rect: '0 55 300 24', anchors: 'left top right', background: 'theme(toolbar-normal)', buttons: [
                    toolbarButton('Follow', '0 0'), toolbarButton('Delete', '-96px 0'), toolbarButton('Move to', '-112px 0')
                ] },
                { view: 'ScrollPane', rect: '0 79 300 548', anchors: 'left top right bottom', childViews: [
                    { view: 'List', id: 'discussions-list', rect: '300 0', anchors: 'left top right', background: 'none', textSelectable: false, rowHeight: 38, render: DiscussionsRender }
                ] },
                { view: 'Box', rect: '0 627 300 24', background: 'theme(box-lblue-bottom)', anchors: 'left bottom right', childViews: [
                    { view: 'Button', rect: '271 5 24 18', backgroundPrefix: 'plus-button-', anchors: 'right bottom', focusable: false }
                ] }
            ] } )
        }
    }
}).attachTo(window, '1000 1000');

uki('#splitRight').bind('handleMove', function(e) {
    if (e.handlePosition > e.dragValue) uki('#splitMain').handlePosition(uki('#splitMain').handlePosition() - (e.handlePosition - e.dragValue) ).layout();
});

uki('#splitRight').handlePosition(400).layout();


function renderListDiscussions(param, data, timeId) {
    // if data is just the id of the discussion to implement, call
    if (null !== param) {
        // TODO: show existing localstorage of discussion
        // TODO: put up some kind of 'loading...' indicator

        loadDiscussions(param);
        return;
    }

    var resources = [];
    for (var i in data) {
        if (null === data[i].RecentEntry) {
            data[i].RecentEntry = { Creator: { username: '___'}, modified: '___'};
        }
        resources[i] = flatten(data[i]);
    }

    var existingTimeId;

    if (timeId && existingTimeId == timeId) {
        console.log(uki('#discussions-list').data());
        console.log(data);
        uki('#discussions-list').data().concat(data);

        // sort through the results would probably be good, but not for now
    }

    uki('#discussions-list').data(resources).parent().layout();
}

function loadDiscussions(param) {
    param = param ? param : {};
    param.filter = param.filter ? param.filter : {};

    var queryParam = {
        'entourage': {
            'RecentEntry': {
                'entourageModel': 'Entry',
                'entourageIdKey': 'discussion_id',
                'resourceIdKey': 'id',
                'singleOnly': true,
                'sort': 'modified',
                'properties': 'id discussion_id modified creator_user_id',
                'entourage': 'Creator'
            }
        },
        'where': []
    }

    if (param.filter.community_id) {
        if ($.isArray(param.filter.community_id)) {
            // standardize to array base
            param.filter.community_id = [param.filter.community_id];
        }

        queryParam.where.push({'community_id': param.filter.community_id});
    }

    if (param.filter.id) {
        if ($.isArray(param.filter.id)) {
            // standardize to array base
            param.filter.id = [param.filter.id];
        }

        queryParam.where.push({'id': param.filter.discussion_id});
    }

    if (param.filter.search) {
        param.filter.search = String(param.filter.search)
            .replace(/[ \t\r\n]+/g, ' ') // normalize string
            .replace(/^[ ](.*?)[ ]$/, '$1') // trim
            .split(' '); // to array of terms

        for (var i in param.filter.search) {
            queryParam.where.push({'~= title': param.filter.search[i], 'id': param.filter.search[i]});
        }

        //$.getJSON('/api/entry', {where: [[{'~ comment': search}, {'~ modified': search}]]}, function(data, textStatus) {
//    var uniqueId = Number(new Date().getTime());
//    renderListDiscussions(null, data.content, uniqueId);
    }

    $.getJSON('/api/discussion', queryParam, function(data, textStatus) {
        renderListDiscussions(null, data.content);
    });
}

function renderDiscussionDetailsPanel(param, data) {
    // if data is just the id of the discussion to implement, call
    if (null !== param) {
        // TODO: show existing localstorage of discussion
        // TODO: put up some kind of 'loading...' indicator

        loadDiscussionDetails(param);
        return;
    }

    var parent = uki('#splitRight')[0];

    var content = ' ';
    for (var i in data.Entries) {
        content += EntriesRender.render(flatten(data.Entries[i]));
    }

    var height = parent.maxY();
    var width = parent.maxX() - parent.leftChildViews()[0].maxX() - 150;

    var resourcePanel = panel(data.title, { rect: width + ' ' + height, background: 'theme(panel-blue)', childViews: [
        { view: 'Label', rect: '0 23 ' + width + ' 50', anchors: 'left top right', multiline: true, scrollable: true, inset: '2 2', textSelectable: true },
        { view: 'Toolbar', rect: '0 73 ' + width + ' 24', anchors: 'left top right', background: 'theme(toolbar-normal)', buttons: [
            toolbarButton('Reply', '-128px 0')
        ] },
        { view: 'ScrollPane', rect: '0 97 ' + width + ' ' + (height - 101), anchors: 'left top right bottom', childViews: [
            { view: 'Label', rect: width + ' 0', anchors: 'left top right', html: content }
        ] }
    ] });

    parent.rightChildViews(resourcePanel);

    parent.rightChildViews()[0].childViews()[2].childViews()[0].resizeToContents('height');

    parent.rightChildViews()[0].layout();
}

function loadDiscussionDetails(id) {
    $.getJSON('/api/discussion', {'where': {'id': id}, 'entourage': ['EntriesWithCreator']}, function(data, textStatus) {
        renderDiscussionDetailsPanel(null, data.content[0]);
    });
}

uki('#nav-all').click(function (eventObj) {
    eventObj.preventDefault();

    resetCommunitiesListState();
});

uki('#usersList').data(users).parent().layout();


function resetCommunitiesListState() {
    uki('#communities-list').selectedIndex(-1);

    resetDiscussionsListState();
}

uki('#communities-list').data(communities).parent().layout();
uki('#communities-list').click(function (eventObj) {
    eventObj.preventDefault();
    var id = $(eventObj.target).parents().andSelf().filter('div:has(a > .communities-row)').last().children().first().attr('href').replace(/.*\/community_id\/(\d+).*?/, '$1');

    resetDiscussionsListState();
});

function resetDiscussionsListState() {
    refreshList = ('undefined' == typeof refreshList) ? true : refreshList;

    uki('#discussions-list').selectedIndex(-1);
    uki('#discussions-search').value('');

    updateDiscussionsList();
}

function updateDiscussionsList() {
    var cId = [];
    var cList = uki('#communities-list');
    var cIndex = cList.selectedIndex();
    if (-1 < cIndex) {
        cId = cList.data()[cIndex].id;
    }

    renderListDiscussions({'filter': {'community_id': cId, 'search': uki('#discussions-search').value()}});
}

uki('#discussions-search').keypress(function (eventObj) {
    if (13 != eventObj.keyCode) {
        return;
    }

    updateDiscussionsList();
});
uki('#discussions-search_launch').click(function (eventObj) {
    updateDiscussionsList();
});

uki('#discussions-list').data(discussions).parent().layout();
uki('#discussions-list').click(function (eventObj) {
    eventObj.preventDefault();
    var id = $(eventObj.target).parents().andSelf().filter('div:has(a > .discussions-row)').last().children().first().attr('href').replace(/.*\/discussion_id\/(\d+).*?/, '$1');

    renderDiscussionDetailsPanel(id);
});
