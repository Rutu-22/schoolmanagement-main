import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from 'src/environments/environment';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class AddressService {

  constructor(private http: HttpClient) {}

  getAddresses(): Observable<any> {
    return this.http.get(environment.ApiUrl + 'getAddresses');
  }

  addAddress(addressData: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'addAddress', addressData);
  }

updateAddress(addressId: number, addressData: any): Observable<any> {
    return this.http.post<any>(`${environment.ApiUrl}updateAddress/${addressId}`, addressData);
  }
  

  deleteAddress(addressId: any): Observable<any> {
    return this.http.post<any>(environment.ApiUrl + 'deleteAddress', { addressId });
  }
}
