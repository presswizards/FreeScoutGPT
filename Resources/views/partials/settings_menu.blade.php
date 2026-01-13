<li @if (Route::is('aiassistant.settings'))class="active"@endif><a href="{{ route('aiassistant.settings', ['mailbox_id'=>$mailbox->id]) }}"><i class="fa-solid fa-robot"></i> AI Assistant</a></li>
