<?php

namespace rethink\typedphp\security;

interface SecurityScheme
{
    // for now, we only support the default security schema
    //
    // {
    //   type: oauth2
    //   flows: {
    //     clientCredentials: {
    //       tokenUrl: "https://example.com/oauth/token"
    //       scopes: {
    //         read: "Grants read access" // scopes loaded from ScopeInterface
    //       }
    //     }
    //   }
}
