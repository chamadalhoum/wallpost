
@component('mail::layout')
{{-- Header --}}
@slot('header')
@endslot

{{-- Body --}}
<!-- Body here -->

{{-- Subcopy --}}
@slot('subcopy')
<div class="rcmBody" style="width: 100%; background-color: #017dc1; overflow-x: hidden; padding:20px ;font-family: 'Ubuntu', sans-serif; max-width: 850px" >

    <!-- header ignored --><!-- meta ignored -->
    <img src="https://wallpost.b-forbiz.com//assets/login/logo_WollPost.png" style="float: left; max-width: 150px; width: 30%">
    <div style="display: flex; justify-content: flex-end!important; align-items: center; float: right; width: 60%px; max-width: 300px">
        <div style="display: block; text-align: right">
            <span style="display: block; color: white; font-size: 14px; line-height: 20px; font-family: 'Ubuntu', sans-serif">Votre chargé de compte</span>
            <span style="display: block; color: white; font-size: 16px; font-weight: bold; line-height: 20px; font-family: 'Ubuntu', sans-serif">xxxxxxx</span>
            <span style="font-family: 'Ubuntu', sans-serif; display: block; color: white; font-size: 16px; line-height: 30px; font-family: 'Ubuntu', sans-serif">xx xx xx xx</span>
            <a href="mailto:#" style="font-family: 'Ubuntu', sans-serif; display: block; color: white; font-size: 14px; padding: 4px 8px; background: #f0ba0e; text-align: center; text-decoration: none!important; border-radius: 8px; line-height: 20px" onclick="return rcmail.command('compose', '#', this)" rel="noreferrer">ENVOYER UN MESSAGE</a>
        </div>
        <img src="https://api-wallpost.b-forbiz.com/public/app/public/icon/user.jpg" style="font-family: 'Ubuntu', sans-serif; display: block; border-radius: 50%; width: 150px; height: 150px; margin: 0px 10px">

    </div>

    <div style="clear: both"></div>
    <div style="margin: 20px 0px; padding: 1px 30px; color: white; line-height: 20px">
        <span style="font-size: 15px"><span style="font-size: 15px; font-family: 'Ubuntu', sans-serif; color: #f0ba0e; padding-right: 10px">|</span>Inscription <span style="font-weight: bold">WallPost</span></span>
        <div style="font-size: 14px; display: block; padding: 20px">
            <span style="font-family: 'Ubuntu', sans-serif; display: block; font-size: 16px; line-height: 25px">Bonjour 
                @if($user->sex='femme')
                    Mme
                @else
                    Mr
                @endif
                <span style="font-weight: 500"> {{$user->firstname}} {{$user->lastname}}</span></span>

        </div>
    </div>
    <div style="margin: 20px 15px; padding: 10px 30px; color: #000; background: white; line-height: 20px; border-radius: 8px">

        <div style="padding-bottom: 0px; border-bottom: 1px dashed gray">

            <br>
            <span style="font-family: Ubuntu, sans-serif; font-size: 14px; display: block">Bonjour  
                @if($user->sex='femme')
                   Mme
                @else
                   Mr
                @endif
                .</span><br>
            <span style="font-family: Ubuntu, sans-serif; font-size: 18px; display: block">je vous informe que tu reçu code de validation a été envoyé
                à l'adresse  <span style="color:#4385f4"> {{$user->address}} </span> le 
                <span style="color:#4385f4"> {{$user->dates}} </span>. 
                les courriers arrivent généralement sous <span style="color:#4385f4">  {{$user->nombreday}} jours </span>.
                Saisissez-le code  pour finaliser la validation qui vous permettre de devenir le propriétaire de votre fiche et rendre visibles toutes vos modifications sur Google.
            </span><br>
            <br>




        </div><br>
        <a href="https://wallpost.cliqeo.com/#/login" style="font-family: 'Ubuntu', sans-serif; display: block; max-width: 300px; margin: 0 auto; color: white; font-size: 16px; box-shadow: 0px 0px 5px 1px rgba(66, 66, 66, 0.3); padding: 10px 15px; background: #f0ba0e; text-align: center; text-decoration: none!important; border-radius: 8px; line-height: 20px" target="_blank" rel="noreferrer">
            ACCÈDEZ À VOTRE ESPACE CLIENT
        </a>
        <br>
    </div>

    <div style="font-family: 'Ubuntu', sans-serif; margin: 20px 0px; padding: 1px 30px; display: block">
        <div style="font-family: 'Ubuntu', sans-serif; float: left; text-align: center">
            <a href="#" style="font-family: 'Ubuntu', sans-serif; display: inline-block; color: white; font-size: 12px; border: 1px solid white; padding: 7px 10px; text-align: center; text-decoration: none!important; border-radius: 8px; line-height: 18px; margin: 10px 0" target="_blank" rel="noreferrer">GÉRER MON COMPTE</a>

            <ul style="display:block;padding: 5px;margin: 0;text-align: center;">
                <li style="list-style: none;display:inline-block;margin: 0;"><a href="#" target="_blank">
                        <img src="https://api-wallpost.b-forbiz.com/public/app/public/icon/picto-1.jpg" style="display: block;max-width: 28px;">
                    </a></li>
                <li style="list-style: none;display:inline-block;margin: 0;border-right: 1px solid #d8f0fc;border-left: 1px solid #d8f0fc;">
                    <a href="https://wallpost.b-forbiz.com/" target="_blank">
                        <img src="https://api-wallpost.b-forbiz.com/public/app/public/icon/picto-2.jpg" style="display: block;max-width: 28px;"/>
                    </a></li>
                <li style="list-style: none;display:inline-block;margin: 0;">
                    <a href="#" target="_blank">
                        <img src="https://api-wallpost.b-forbiz.com/public/app/public/icon/picto-3.jpg" style="display: block;max-width: 28px;">
                    </a></li>

            </ul>
        </div>

        <div style=" font-family: 'Ubuntu', sans-serif;float:right;">
            <img src="https://wallpost.b-forbiz.com/assets/login/logo_WollPost.png" style="display: block;max-width: 100px;margin: 0 auto">

            <ul style="display:block;padding: 5px;margin: 0;text-align: center;">
                <li style="list-style: none;display:inline-block;margin: 0;">
                    <a href="https://www.instagram.com/agence_cliqeo/" target="_blank">
                        <img src="https://api-wallpost.b-forbiz.com/public/app/public/icon/insta.jpg" style="display: block;max-width: 28px;"></a></li>
                <li style="list-style: none;display:inline-block;margin: 0;">
                    <a href="https://fr.linkedin.com/company/bforbiz/" target="_blank">
                        <img src="https://api-wallpost.b-forbiz.com/public/app/public/icon/linked-in.jpg" style="display: block;max-width: 28px;"/></a></li>
                <li style="list-style: none;display:inline-block;margin: 0;">
                    <a href="https://www.facebook.com/AgenceBforbiz/" target="_blank">
                        <img src="https://api-wallpost.b-forbiz.com/public/app/public/icon/facebook.jpg" style="display: block;max-width: 28px;">
                    </a></li>

            </ul>
        </div>
    </div>

    <img width="1" height="1" src="program/resources/blocked.gif"></div>

@component('mail::subcopy')

@endcomponent
@endslot


{{-- Footer --}}
@slot('footer')
@component('mail::footer')

@endcomponent
@endslot
@endcomponent

