<?php
const JWT_PUBLIC_KEY = <<<JWT
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAsyouK0l5khOZrP3OmceU
HtE5Akipc51lUANLXBsG4u1L0aNxG9Znk6iQkPsfprybwXPIVFp1HdtUKhqQ/DN2
hvu6GGdSNNhlpGLeDO4lzFTgfV49+GuOPeJtgHfBtN8kxqqYadJNYFT8jG9Du4Zs
/9idNN1idGUt1r/CJXwR13aTnvl0whvJ6s1pf1ekG53al0il2XpszlL4GjW28OHk
Q/VTe6ZKDEK2QO16cszfKapMhXAyp56+1XoILWpO9lu5DJD6fj8uhry1qAgV8fUs
Vhn0ZSR64GrC5R0SwiU9DjrwtMigfwu9BesJ26DAH2eUJWSZjWKv/ECsJzqaTgbP
UA7zwDIfOtXFYwRhbG8G7nDdLl/XxWnwpW00WL3kNo1lNdxs8XdSfAQ2fK9PXDF5
EoqJeTmd+fS/DLzTqAVKHvjmDtcdicWsujpgmOB6sirBzGPzCAkr0OWtu/UH6wA7
xPCPNaO3om/+O83fw1T3S2CSGtDB6hEI/VdJ6dD/JMwzonOA70VjIJzrV48aiZZv
zDYAPwfbZxNbCwYQCXAJgOQublvwG+2r6m9C1SSMzH4ofVGN6ZUbEHuBOIC69QWm
wXCmnspABGsKI4eWW6ID2Dl9fZu1/txusHJ78qTvefVIxQmfoXCaepJrPYYi0jnt
gO9O/0Qt/ZXrCI1F+NYSwyECAwEAAQ==
-----END PUBLIC KEY-----
JWT;
return [
  'base_oauth_url' => env('ImapOauth2_BASE_URL', 'http://localhost:3000'),
  'client_id' => env('ImapOauth2_CLIENT_ID'),
  'client_secret'=> env('ImapOauth2_CLIENT_SECRET'),
  'jwt_public_key'=> env('ImapOauth2_JWT_PUBLIC_KEY', JWT_PUBLIC_KEY),
  'api_microservice_url' => env('API_MICROSERVICE_URL', 'http://staging.api-gateway.ebomb.edu.vn/v1'),
  'profile_type' => env('PROFILE_TYPE', 'hr'), // hr || crm
  // other options...
];