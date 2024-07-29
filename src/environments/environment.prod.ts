// This file can be replaced during build by using the `fileReplacements` array.
// `ng build --prod` replaces `environment.ts` with `environment.prod.ts`.
// The list of file replacements can be found in `angular.json`.

export const environment = {
  production: true,
  env:'prod',
  hubApiUrl: 'http://localhost:5087/api/',
  appTierUrl: 'http://localhost:8080/Api/',
  mqtt: {
    server: 'dev2at01.dev.apdcomms.co.uk',
    protocol: 'ws',
    port: 15675,
    userName: 'aspire_admin',
    password: 'APDc0mm$',
  },
};
