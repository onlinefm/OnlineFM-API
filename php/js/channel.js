
var Channel = function(url, timeToRefresh, minFutureLength) {

    var _url = url;
    var _data = null;
    var _minFutureLength = minFutureLength;
    var _timeToRefresh = timeToRefresh * 1000;
    var _timer = null;
    var _listeners = [];

    this.init = function(data) {
        if (data) {
            _data = data;
        }
        else {
            _update();
        }
        _timer = setTimeout(_refresh, _timeToRefresh);
    }

    this.subscribe = function(func) {
        _listeners.push(func);
    }

    var _update = function() {
        if (_data && _data.future.length > _minFutureLength)
            return;

        $.getJSON(_url, function(data) {
            if (data.future.length) {
                _data = data;
                //window.console.log(_data);
            }
        });
    }

    var _getTimeToRefresh = function() {
        if (_data.future.length && _data.current.length) {
            return (_data.future[_data.future.length - 1].play_begin - _data.current[0].play_begin) * 1000;
        }
        return 100000; //try to refresh before 100 sec if playlist is empty
    }

    var _refresh = function() {
        //window.console.log(_url);
        clearTimeout(_timer);

        //move songs from current to past and from future to current
        if (_data.current.length) {
            _data.past.unshift(_data.current.pop());
        }
        if (_data.future.length) {
            _data.current.unshift(_data.future.pop());
        }

        //call listeners
        for (i in _listeners) {
            _listeners[i](_data);
        }

        //get new refresh time
        _timeToRefresh = _getTimeToRefresh();
        /////window.console.log("set timeout " + _timeToRefresh/1000);
        _timer = setTimeout(_refresh, _timeToRefresh);

        //get new data from server if need
        _update();
    }

}


var doProcessSongForChItem = function(el, song) {
    if (song.title && song.artist) {
        var songHtml = null;
        var artistHtml = null;
        if (0 && song.artistUrl) {
            songHtml = "<a class='song' href='" +
                       song.artistUrl +
                       "' title='" +
                       song.title +
                       "'>" +
                       song.titleShort +
                       "</a>";
            artistHtml = "<a class='artist' href='" +
                         song.artistUrl +
                         "' title='" +
                         song.artist +
                         "'>" +
                         song.artistShort +
                         "</a>";
        }
        else {
            songHtml = "<span class='song'>" +
                       song.titleShort +
                       "</span>";
            artistHtml = "<span class='artist'>" +
                         song.artistShort +
                         "</span>";
        }
        el.find(".song").replaceWith(songHtml);
        el.find(".artist").replaceWith(artistHtml);
    }
}

var createRefreshForChItem = function(id) {
    var el = $("#channel-item-" + id);
    return function(data) {
        if (data.current.length) {
            var song = data.current[0];
            if (song.coverChItem) {
                var img = el.find("div.cover img");
                img.attr("src", song.coverChItem);
                if (song.hasCover && song.albumTitle) {
                    img.attr("alt", song.albumTitle);
                    el.find("div.cover a").attr("title", song.albumTitle);
                }
                else {
                    img.attr("alt", el.find("div.meta h2 a").text());
                    el.find("div.cover a").attr("title", el.find("div.meta h2 a").text());
                }
            }
            doProcessSongForChItem(el.find("div.playing-now"), song);
        }
        if (data.future.length) {
            var song = data.future[data.future.length - 1];
            doProcessSongForChItem(el.find("div.playing-next"), song);
            //preload cover
            var cacheMapImage = document.createElement('img');
            cacheMapImage.src = song.coverChItem;
        }
    }
}

var initChItems = function(ids, url) {
    var channels = {};
    $.getJSON(url + "list?callback=?", function(data) {
        $.each(data, function(idStr, playlist) {
            var id = Number(idStr);
            if (-1 != $.inArray(id, ids)) {
                var channel = new Channel(url + id + "?callback=?", playlist["timeToUpdate"], 3);
                channel.init(playlist);
                channel.subscribe(createRefreshForChItem(id));
                channels[id] = channel;
                //preload next cover
                if (playlist.future.length) {
                    var cacheMapImage = document.createElement('img');
                    cacheMapImage.src = playlist.future[playlist.future.length - 1].coverChItem;
                }
            }
        });
    });
}


var doProcessCoverForChannel = function(el, song) {
    var img = el.find("img");
    img.attr("src", song.coverPlayingNow);
    if (song.hasCover && song.albumTitle) {
        img.attr("alt", song.albumTitle);
    }
    else {
        img.attr("alt", "");
    }
    if (song.artistUrl) {
        var a = el.find("a");
        if (a.length) {
            a.attr("href", song.artistUrl);
            a.attr("title", song.albumTitle);
        }
        else {
            a = $("<a href='" +
                      song.artistUrl +
                      "' title='" +
                      song.artist +
                      "'>" +
                      "</a>");
            a.append(img);
            el.html(a);
        }
    }
    else {
        var a = el.find("a");
        if (a.length) {
            el.html(img);
        }
    }
}

var createArtistHtmlForChannel = function(song) {
    var html = song.artistShort;
    if (0 && song.artistUrl) {
        html = "<a href='" +
                    song.artistUrl +
                    "' title='" +
                    song.artist +
                    "'>" +
                    html +
                    "</a>";
    }
    return html;
}
var doProcessArtistForChannel = function(el, song) {
    el.html(createArtistHtmlForChannel(song));
}
var createSongHtmlForChannel = function(song) {
    var html = song.titleShort;
    if (0 && song.artistUrl) {
        html = "<a href='" +
                    song.artistUrl +
                    "' title='" +
                    song.title +
                    "'>" +
                    html +
                    "</a>";
    }
    return html;
}
var doProcessSongForChannel = function(el, song) {
    el.html(createSongHtmlForChannel(song));
}

var doProcessAlbumForChannel = function(el, song) {
    var albumTitle = song.albumTitle;
    var albumYear = song.albumYear;
    if (albumTitle || albumYear) {
        if (albumTitle) {
            if (0 && song.artistUrl && song.artistId) {
                var html = "<a href='" +
                           song.artistUrl + "#" + song.artistId +
                           "' title='" +
                           albumTitle +
                           "'>" +
                           albumTitle +
                           "</a>";
                el.find("span.title").html(html);
            }
            else {
                el.find("span.title").text(albumTitle);
            }
        }
        if (albumYear) {
            el.find("span.year").text(", " + albumYear).show();
        }
        else {
            el.find("span.year").hide();
        }
        el.show();
    }
    else {
        el.hide();
    }
}

var refreshChannelPlayingNow = function(data) {
    if (data.past.length) {
        var elPrev = $("#playing-now li.prev");
        var songPrev = data.past[0];
        doProcessArtistForChannel(elPrev.find("h2.artist"), songPrev);
        doProcessSongForChannel(elPrev.find("h2.song"), songPrev);
        doProcessAlbumForChannel(elPrev.find("p.album"), songPrev);
    }

    if (data.current.length) {
        var elNow = $("#playing-now li.now");
        var songNow = data.current[0];
        doProcessCoverForChannel(elNow.find("div.cover"), songNow);
        doProcessArtistForChannel(elNow.find("h2.artist"), songNow);
        doProcessSongForChannel(elNow.find("h2.song"), songNow);
        doProcessAlbumForChannel(elNow.find("p.album"), songNow);
    }

    if (data.future.length) {
        //next 1
        var elNext = $("#playing-now li.next:first");
        var songNext = data.future[data.future.length - 1];
        doProcessCoverForChannel(elNext.find("div.cover"), songNext);
        doProcessArtistForChannel(elNext.find("h2.artist"), songNext);
        doProcessSongForChannel(elNext.find("h2.song"), songNext);
        doProcessAlbumForChannel(elNext.find("p.album"), songNext);

        if (data.future.length > 1) {
            //next 2
            var elNext2 = $("#playing-now li.next2");
            var songNext2 = data.future[data.future.length - 2];
            doProcessCoverForChannel(elNext2.find("div.cover"), songNext2);
        }
    }
}
var refreshChannelPlaylist = function(data) {
    var table = $("div#playlist table");
    if (data.past.length) {
        var trsPrev = table.find("tr.prev");
        trsPrev.each(function(index, tr) {
            song = data.past[trsPrev.length - index - 1];
            var td = $(tr).find("td.name");
            td.empty();
            var tdTime = $(tr).find("td.time");
            tdTime.empty();
            if (song) {
                var artistHtml = createArtistHtmlForChannel(song);
                var songHtml = createSongHtmlForChannel(song);
                td.append(artistHtml);
                td.append(" - ");
                td.append(songHtml);
                tdTime.append(song.lengthFormat);
            }
        });
    }
    if (data.current.length) {
        var elNow = table.find("tr.now");
        var songNow = data.current[0];
        var artistHtml = createArtistHtmlForChannel(songNow);
        var songHtml = createSongHtmlForChannel(songNow);
        //var legendNow = elNow.find("div.legend");
        var tdNow = elNow.find("td.name");
        tdNow.empty();
        //tdNow.append(legendNow);
        tdNow.append(artistHtml);
        tdNow.append(" - ");
        tdNow.append(songHtml);
        var tdTime = elNow.find("td.time");
        tdTime.empty();
        tdTime.append(songNow.lengthFormat);
    }
    if (data.future.length) {
        var trsNext = table.find("tr.next");
        trsNext.each(function(index, tr) {
            song = data.future[data.future.length - index - 1];
            var td = $(tr).find("td.name");
            //var legendNext = td.find("div.legend");
            td.empty();
            var tdTime = $(tr).find("td.time");
            tdTime.empty();
            if (song) {
                var artistHtml = createArtistHtmlForChannel(song);
                var songHtml = createSongHtmlForChannel(song);
                /*if (legendNext) {
                    td.append(legendNext);
                }*/
                td.append(artistHtml);
                td.append(" - ");
                td.append(songHtml);
                tdTime.append(song.lengthFormat);
            }
        });
    }
}

var PositionControl = function() {

    var _control = null;
    var _width = null;
    var _timer = null;
    var _length = null;
    var _margin = null;
    var _step = null;

    this.init = function() {
        _control = $(".control");
        _width = _control.parent().parent().width();
    }

    var _clear = function() {
        clearInterval(_timer);
        _length = 0;
        _margin = 0;
        _step = 0;
        _control.css('margin-left', _margin);
    }

    var _move = function() {
        if (_margin < _width) {
            _margin += _step;
            _control.css('margin-left', _margin);
        }
    }

    this.restart = function(length) {
        _clear();
        _length = length;
        _step = _width / _length;
        _timer = setInterval(_move, 1000);
    }

    this.setPosition = function(position) {
        if (position < _length) {
            _margin = position * _step;
            _control.css('margin-left', _margin);
        }
    }

}

var getRestartPositionControl = function(fullCurrentLength, leftCurrentLength) {
    var positionControl = new PositionControl();
    positionControl.init();
    positionControl.restart(fullCurrentLength);
    positionControl.setPosition(fullCurrentLength - leftCurrentLength);
    return function(data) {
        if (data.current.length) {
            var song = data.current[0];
            positionControl.restart(song.lengthMin * 60 + song.lengthSec);
        }
    }
}
var getRefreshChannelPlayingNow = function() {
    return function(data) {
        return refreshChannelPlayingNow(data);
    }
}
var getRefreshChannelPlaylist = function() {
    return function(data) {
        return refreshChannelPlaylist(data);
    }
}

var initChannel = function(id, url) {
    $.getJSON(url + "?callback=?", function(playlist) {
        var channel = new Channel(url + "?callback=?", playlist["timeToUpdate"], 6);
        channel.init(playlist);
        channel.subscribe(getRefreshChannelPlayingNow());
        channel.subscribe(getRefreshChannelPlaylist());
        var fullCurrentLength = 0;
        if (playlist.current.length) {
            fullCurrentLength = playlist.current[0].lengthMin * 60 + playlist.current[0].lengthSec;
        }
        channel.subscribe(getRestartPositionControl(fullCurrentLength, playlist["timeToUpdate"]));

        //preload next cover
        if (playlist.future.length) {
            var cacheMapImage = document.createElement('img');
            cacheMapImage.src = playlist.future[playlist.future.length - 1].coverChItem;
        }
    });
}

$(function() {
    //init playlist hover
    $("#playlist-top tr").hover(
        function() {
            $(this).css('background-color', '#e5e8ea');
        },
        function() {
            $(this).css('background-color', '#ffffff');
        }
    );

    $("#top-songs li").hover(
        function() {
            $(this).css('background-color', '#e5e8ea');
        },
        function() {
            $(this).css('background-color', '#ffffff');
        }
    );

})


// ************************************************

var PlayerClass = function() {
    var _status = 0;
    var _toolbar = 0;
    var _location = 0;
    var _menubar = 0;
    var _directories = 0;
    var _resizable = 1;
    var _scrollbars = 0;
    var _width = 600;
    var _height = 550;

    this.getPlayer = function(lang, channelId) {
        if(!channelId) return false;
        var _params = channelId;
        var options = 'status='+_status+', toolbar='+_toolbar+', location='+_location+', menubar='+_menubar+', resizable='+_resizable+', scrollbars='+_scrollbars+', width='+_width+', height='+_height;
        var player = window.open('http://online.fm/player/'+lang+'/'+_params ,'player', options);
        player.focus();
        return player;
    }
}

var startPlayer = function(lang, playerId) {
    var p = new PlayerClass();
    p.getPlayer(lang, playerId);
    return false;
}

