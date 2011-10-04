/**
 * WePortal JS File
 * © Shitiz "Dragooon" Garg
 */
$(function () {
    // Based on $.dragslide by Nao
    var drag = {};
    $.fn.drag = function (options) {
        options = options || {};
        drag.options = options;

        return this.each(function () {
            $(this).data('orig_offset', $(this).offset());
            $(this).data('options', options);

            if (options.initiate_selector)
                el = $(this).find(options.initiate_selector);
            else
                el = $(this);

            $(el).css('cursor', 'move');

            $(el).bind('mousedown', { el: this }, function (e) {
                if (e.button != 0)
                    return true;

                if ($(e.data.el).data('options').dragstart)
                    $(e.data.el).data('options').dragstart.apply(e.data.el, [e]);

                $(e.data.el).startdrag(e);

                return false;
            });
        });
    };
    $.fn.undrag = function () {
        return this.each(function () {
            if (drag.options.initiate_selector)
                el = $(this).find(drag.options.initiate_selector);
            else
                el = $(this);

            $(el).css('cursor', 'default');
            $(el).unbind('mousedown');
        });
    };

    $.fn.startdrag = function (e) {
        drag.in_progress = true;
        drag.element = this;

        $(this).attr('data-in-drag', true);

        var offTop = e.pageY - $(this).offset().top;
        var offLeft = e.pageX - $(this).offset().left;

        drag.offset_top = offTop;
        drag.offset_left = offLeft;
        drag.original_offset = $(this).offset();

        $(this).data('orig_width', $(this).css('width'));
        $(this).css('width', $(this).width()).css('position', 'absolute').css('z-index', 999).updatePos(e, e.pageX, e.pageY);
    };
    $.fn.updatePos = function (e, mouseX, mouseY) {
        if ($(this).data('options').dragmove)
            $(this).data('options').dragmove.apply(drag.element, [e, mouseX, mouseY]);

        $(this).css({
            left: mouseX - drag.offset_left,
            top: mouseY - drag.offset_top
        });
    };
    $(document).mousemove(function (e) {
        if (drag.in_progress) {
            $(drag.element).updatePos(e, e.pageX, e.pageY);
            return false;
        }
    });
    $(document).mouseup(function (e) {
        if (drag.in_progress) {
            $(drag.element).css('width', $(drag.element).data('orig_width'));
            $(drag.element).attr('data-in-drag', false);
            drag.in_progress = false;
            if ($(drag.element).data('options').dragend)
                $(drag.element).data('options').dragend.apply(drag.element, [e]);
        }
    });

    var getItemAfterByY = function (items, mouseX, mouseY, debug) {
        for (i in items) {
            if (mouseY >= items[i][1])
                continue;
            return items[i][4];
        }

        return false;
    };
    var getCoords = function (elements) {
        var coords = [];
        $(elements).each(function () {
            var x1 = $(this).offset().left;
            var y1 = $(this).offset().top;
            var x2 = x1 + $(this).width();
            var y2 = y1 + $(this).height();
            coords[coords.length] = [x1, y1, x2, y2, this];
        });

        return coords;
    };

    var getIntersection = function (areas, mouseX, mouseY) {
        for (i = 0; i < areas.length; i++) {
            var x1 = areas[i][0];
            var y1 = areas[i][1];
            var x2 = areas[i][2];
            var y2 = areas[i][3];
            var el = areas[i][4];

            if (mouseX > (x1 - 50) && mouseX < (x2 + 50) && mouseY > (y1 - 50) && mouseY < (y2 + 50))
                return el;
        }

        return false;
    };

    // Sends an ajax request to update the block's position in the DB
    var updateBlocksPos = function () {
        var query = [];
        $('[data-portal-block=true]').each(function () {
            var side = $(this).parent('[data-portal-bar=true]').attr('data-bar-side');
            var block_id = $(this).attr('data-block-id');
            query[query.length] = 'bars[' + side + '][] = ' + block_id + '';
        });

        $.ajax({
            data: query.join('&'),
            type: 'post',
            url: we_script + '?action=portal;area=blockupdate'
        });
    };

    var portalBlockDragstart = function (e) {
        $('[data-portal-bar=true]').addClass('wep_bar_drag');
        $(this).css('opacity', 0.5);
    };
    var portalBlockDragmove = function (e, mouseX, mouseY) {
        var droppables = getCoords($('[data-portal-bar=true]'));
        if (droppables.length <= 0)
            return true;

        for (i in droppables) {
            var el = droppables[i][4];
            if ($(el).data('drop_extended')) {
                $(el).data('drop_extended', false);
                $(el).height('100%');
            }
        }

        if (drag.current_extended)
            $(drag.current_extended).css('margin-top', 10);

        var intersects = getIntersection(droppables, e.pageX, e.pageY);
        if (!intersects)
            return true;

        var items = getCoords($(intersects).find('li:not([data-in-drag=true])'));

        if (items.length <= 0)
            return true;

        var after_item = getItemAfterByY(items, mouseX, mouseY);

        // If there is no item after, then we're adding the item at the end
        if (!after_item && !$(intersects).data('drop_extended')) {
            $(intersects).data('orig_height', $(intersects).height());
            $(intersects).data('drop_extended', true);
        }
        else {
            drag.current_extended = $(after_item);
            $(after_item).css('margin-top', $(drag.element).height());
        }
    };
    var portalBlockDragend = function (e) {
        $(this).css({ 'opacity': $(this).attr('data-block-hidden') == 'true' ? 0.5 : 1 });

        $('[data-portal-bar=true]').removeClass('wep_bar_drag');

        var droppables = getCoords($('[data-portal-bar=true]'));
        if (droppables.length <= 0)
            return true;

        var intersects = getIntersection(droppables, e.pageX, e.pageY);
        if (!intersects) {
            $(drag.element).css({
                left: drag.original_offset.left,
                top: drag.original_offset.top
            });
            $(drag.element).css('position', 'static');
            $(drag.element).css('z-index', '0');
            $(drag.element).next().css('margin-top', 10);
            return false;
        }

        var items = getCoords($(intersects).find('li:not([data-in-drag=true])'));

        if (items.length <= 0) {
            $(drag.element).css('position', 'static');
            $(drag.element).css('z-index', '0');
            $(drag.element).appendTo(intersects);
            return true;
        }

        var after_item = getItemAfterByY(items, e.pageX, e.pageY, true);

        $(this).css({
            'position': 'static',
            'z-index': '0'
        });

        if (drag.current_extended)
            $(drag.current_extended).css('margin-top', 10);

        if (!after_item)
            $(this).appendTo(intersects);
        else
            $(this).insertBefore(after_item);

        updateBlocksPos();
    };
    $.weportal = {};
    $.weportal.modifying = false;

    $('.wep_block').each(function () {
        $(this).find('header').dblclick(function () {
            $.weportal.modify();
        });
    });

    $.weportal.modify = function () {
        if (!$.weportal.modifying) {
            $('[data-block-hidden=true]').addClass('wep_visible');

            $('[data-portal-block=true]').find('.wep_disable').click(function () {
                var block = $(this).parent('[data-portal-block=true]');
                block.css('opacity', 0.5);
                block.attr('data-block-hidden', 'true');
                block.addClass('wep_hidden');
                block.addClass('wep_visible');

                $(this).hide();
                block.find('.wep_enable').show();

                $.ajax({
                    type: 'post',
                    url: we_script + '?action=portal;area=blockupdate',
                    data: 'blocks_disabled[]=' + block.attr('data-block-id')
                });
            });
            $('[data-portal-block=true]').find('.wep_enable').click(function () {
                var block = $(this).parent('[data-portal-block=true]');
                block.css('opacity', 1);
                block.attr('data-block-hidden', 'false');
                block.removeClass('wep_hidden');
                block.removeClass('wep_visible');

                $(this).hide();
                block.find('.wep_disable').show();

                $.ajax({
                    type: 'post',
                    url: we_script + '?action=portal;area=blockupdate',
                    data: 'blocks_enabled[]=' + block.attr('data-block-id')
                });
            });

            $('[data-portal-block=true]:not([data-block-hidden=true])').find('.wep_disable').show();
            $('[data-portal-block=true][data-block-hidden=true]').find('.wep_enable').show();

            $('[data-portal-bar=true]').addClass('modifying');
            $.weportal.modifying = true;

            $('[data-portal-block=true]').drag({
                initiate_selector: '.title',
                dragstart: portalBlockDragstart,
                dragmove: portalBlockDragmove,
                dragend: portalBlockDragend
            });
        }
        else {
            $('[data-portal-block=true]:not([data-block-hidden=true])').find('.wep_disable').hide();
            $('[data-portal-block=true][data-block-hidden=true]').find('.wep_enable').hide();

            $('[data-block-hidden=true]').removeClass('wep_visible');

            $.weportal.modifying = false;

            $('[data-portal-block=true]').undrag();

            $('[data-portal-bar=true]').removeClass('modifying');
        }
    };
});