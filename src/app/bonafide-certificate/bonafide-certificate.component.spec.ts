import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BonafideCertificateComponent } from './bonafide-certificate.component';

describe('BonafideCertificateComponent', () => {
  let component: BonafideCertificateComponent;
  let fixture: ComponentFixture<BonafideCertificateComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BonafideCertificateComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BonafideCertificateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
