import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initCircle} from './initCircle'
import notificationSound from '../sound/notification.mp3'
import {setSnackbar} from './myToastr';
import {TabUtils} from './tabBroadcast'

function initNotofication() {
    Push.Permission.request();
}

function masterNotify(data) {

    Push.Permission.request();
    if (data.type === 'notification') {
        notifymoderator(data)
    } else if (data.type === 'refresh') {
        refresh(data)
    } else if (data.type === 'modal') {
        loadModal(data)
    } else if (data.type === 'redirect') {
        redirect(data)
    } else if (data.type === 'snackbar') {
        setSnackbar(data.message, data.color)
    } else if (data.type === 'newJitsi') {

    } else if (data.type === 'endMeeting') {
        endMeeting(data)
    } else if (data.type === 'reload') {
        setTimeout(function () {
            location.reload();
        }, data.timeout)
    } else {
        alert('Error, Please reload the page')
    }
}


function notifymoderator(data) {
    var audio = new Audio(notificationSound);
    TabUtils.lockFunction('audio'+data.messageId,function (){audio.play()},1500);

    showPush(data);

    setSnackbar(data.message, 'success');

    $('.dragger').addClass('active');

    $('#sliderTop')
        .addClass('notification')
        .css('top', '0px')
        .mouseover(function (e) {
            $('.dragger').removeClass('active');
            $('#sliderTop')
                .removeClass('notification')
                .css('top', '-' + $('#col-waitinglist').outerHeight() + 'px');
        })
}


function refresh(data) {
    var reloadUrl = data.reloadUrl;

    $('#waitingUserWrapper').load(reloadUrl, function () {
        if (!$('#sliderTop').hasClass('notification')) {
            $('#sliderTop').css('top', '-' + $('#col-waitinglist').outerHeight() + 'px');
        }
        initCircle();
        countParts();
    });
}

function endMeeting(data) {

    if (window.opener == null) {

        setTimeout(function () {
            window.location.href = data.url;
        }, data.timeout)
    } else {
        setTimeout(function () {
            window.close();
        }, data.timeout)
    }
}

function loadModal(data) {
    $('#loadContentModal').html(data.content).modal('show');
}


function redirect(data) {
    setTimeout(function () {
        window.location.href = data.url;
    }, data.timeout)

}

function countParts() {
    $('#lobbyCounter').text($('.waitingUserCard').length);
}

function showPush(data){
    TabUtils.lockFunction(data.messageId, function () {
        if (document.hidden) {
            Push.create(data.title, {
                body: data.pushNotification,
                icon: '/favicon.ico',
                onClick: function (ele) {
                    window.focus();
                    this.close();
                }
            });
        }
    },1500)
}

export {masterNotify, initNotofication}
