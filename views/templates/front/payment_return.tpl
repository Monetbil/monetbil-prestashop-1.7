{*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License or any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*}
<h2>{l s='Welcome back' mod='monetbil'}</h2>
<p>{$msg_details|escape:'htmlall':'UTF-8' nofilter}</p>
<a class="btn btn-default" href="{$link->getPageLink('index', true)|escape:'htmlall':'UTF-8' nofilter}">
    <i class="icon icon-chevron-left"></i> {l s='Continue shopping' mod='monetbil'}
</a>
<script type="text/javascript">location.href = "{$link->getPageLink('index', true)|escape:'htmlall':'UTF-8' nofilter}";</script>