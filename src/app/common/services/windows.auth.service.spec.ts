// import { HttpClient } from '@angular/common/http';
// import { TestBed } from '@angular/core/testing';
// import {
//   HttpClientTestingModule,
//   HttpTestingController,
// } from '@angular/common/http/testing';
// import { WindowsAuthService } from './windows.auth.service';

// describe('WindowsAuthService', () => {
//   let httpClient: HttpClient;
//   let service: WindowsAuthService;
//   let httpController: HttpTestingController;

//   beforeEach(() => {
//     localStorage.setItem('userName','test');
//     TestBed.configureTestingModule({
//       imports: [HttpClientTestingModule],
//       providers:[WindowsAuthService]
//     });
//     httpClient = TestBed.inject(HttpClient);
//     service = TestBed.inject(WindowsAuthService);
//     httpController = TestBed.inject(HttpTestingController);
//   });

//   it('should be created', () => {
//     expect(service).toBeTruthy();
//   });

//   it('should call Get user name', () => {
//     const userName: string =  service.getUserName();
//      expect(userName).toBe('test');
//    });

//    it('should call Get encoded user name', () => {
//     const userName: string =  service.getEncodedUserName();
//      expect(userName).toBeInstanceOf(String);
//    });

//   it('should return setting value', () => {
//     const expectedResult: any[] = [
//       {
//           key: 1,
//           value: 'Supervisor',
//           setting: 'role',
//       },
//     ];
//     service.getUserSettingsByAdName("role",service.getUserName()).subscribe((value) => {
//       expect(value).toEqual(expectedResult);
//     });
//     const req = httpController.expectOne('http://localhost:8081/Api/Settings/Value/User/role/test');
//     httpController.verify();
//     req.flush(expectedResult);
//     httpController.verify();
//   });
// });
