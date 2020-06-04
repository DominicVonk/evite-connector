# evite-connector

## List all active events:

```
GET https://evite.dovoco.de/?code=<auth_code>
```

## Add new event:

```
POST https://evite.dovoco.de/?code=<auth_code>
{
    "event_id": "<eplace_event_id>"
}
```

## Deactivate event watcher:

```
PUT https://evite.dovoco.de/disable?code=<auth_code>
{
    "event_id": "<eplace_event_id>"
}
```

## Reactivate event watcher:

```
PUT https://evite.dovoco.de/enable?code=<auth_code>
{
    "event_id": "<eplace_event_id>"
}
```

## Delete event watcher:

```
DELETE https://evite.dovoco.de/?code=<auth_code>
{
    "event_id": "<eplace_event_id>"
}
```

## Force event sync:

```
POST https://evite.dovoco.de/sync?code=<auth_code>
{
    "event_id": "<eplace_event_id>"
}
```
