// This file can be replaced during build by using the `fileReplacements` array.
// `ng build --prod` replaces `environment.ts` with `environment.prod.ts`.
// The list of file replacements can be found in `angular.json`.
//hubApiUrl: 'http://localhost:5087/api/',
//appTierUrl: 'http://localhost:8080/Api/',
export const environment = {
  production: false,
  env:'test',
  hubApiUrl: 'http://humiis01.dev.apdcomms.co.uk:8110/api/',
  appTierUrl: 'http://humiis01.dev.apdcomms.co.uk:8080/Api/',
  mqtt: {
    server: 'dev2at01.dev.apdcomms.co.uk',
    protocol: 'ws',
    port: 15675,
    userName: 'aspire_admin',
    password: 'APDc0mm$',
  },
};
