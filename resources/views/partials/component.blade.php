<li class="list-group-item {{ $component->group_id ? "sub-component" : "component" }}">
    @if($component->link)
    <a href="{{ $component->link }}" target="_blank" class="links">{!! $component->name !!}</a>
    @else
    {!! $component->name !!}
    @endif

    @if($component->description)
    <i class="ion ion-ios-help-outline help-icon" data-toggle="tooltip" data-title="{{ $component->description }}" data-container="body"></i>
    @endif

    <div class="pull-right">
        <small class="text-component-{{ $component->status }} {{ $component->status_color }}" data-container="body" data-toggle="tooltip" title="{{ trans('cachet.components.last_updated', ['timestamp' => $component->updated_at_formatted]) }}">{{ $component->human_status }}</small>
    </div>

    <div class="status-history-wrapper">
        <div class="status-history-component">
            @foreach($component->statusHistory()->get('days') as $summaryDay)
            <div data-toggle="tooltip" data-title="{{ $summaryDay->get('description') }}" data-container="body" data-html="true">
                <svg class="status-history-bar" id="status-history-component-{{ $component->id }}-{{ $loop->index }}" preserveAspectRatio="none" height="34" width="100%">
                    <rect class="rect-component-{{ $summaryDay->get('summary') }} day-{{ $loop->index }}" height="100%" width="100%" x="0" y="0"></rect>
                </svg>
            </div>
            @endforeach
        </div>
        <div class="status-history-component" >
            <div class="legend-item" >
                <small class="text-component-0 greys">90 days ago</small>
            </div>
            <div class="legend-item" >
                <small class="text-component-0 greys">{{ $component->statusHistory()->get('availability') }}% uptime</small>
            </div>
            <div class="legend-item">
                <small class="text-component-0 greys">Today</small>
            </div>
        </div>
    </div>
</li>