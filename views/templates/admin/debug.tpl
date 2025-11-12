<div class="panel">
    <div class="panel-heading">
        <i class="icon-cog"></i>
        Uduny Debug
    </div>
    <div class="form-wrapper">
        <div class="form-group">
            <label class="control-label col-lg-4">Debug Date:</label>
            <div class="col-lg-2">
                <select id="DATE-SELECTOR">
                    {foreach from=$debug.dates key=key item=date}
                        <option value="{$date}"{if $key == 0}selected{/if}>{$date}</option>
                    {/foreach}
                </select>
                <p class="help-block">Debug date selector</p>
            </div>
            <div class="col-lg-3">
                <a href="javascript:void(0)" class="btn btn-primary" id="REFRESH-DATE">Refresh</a>
                <a href="javascript:void(0)" class="btn btn-warning" id="DELETE-DATE">Delete Current Log</a>
                <a href="javascript:void(0)" class="btn btn-danger" id="DELETE-ALL">Clear all</a>
            </div>
        </div>
        <div class="form-group">
            <textarea id="DEBUG-FIELD" style="width:100%;min-height:500px;" disabled></textarea>
        </div>
    </div>
</div>